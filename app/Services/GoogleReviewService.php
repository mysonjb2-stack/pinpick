<?php

namespace App\Services;

use App\Models\GooglePlaceCache;
use App\Models\GoogleReview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_places.api_key', '');
    }

    public function fetchAndCache(string $placeId): ?GooglePlaceCache
    {
        if (!$this->apiKey || !$placeId) return null;

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,reviews',
            ])->timeout(8)->get("https://places.googleapis.com/v1/places/{$placeId}", [
                'languageCode' => 'ko',
            ]);

            if (!$response->successful()) return null;

            $data = $response->json();

            $cache = GooglePlaceCache::updateOrCreate(
                ['google_place_id' => $placeId],
                [
                    'rating' => $data['rating'] ?? 0,
                    'review_count' => $data['userRatingCount'] ?? 0,
                    'fetched_at' => now(),
                ]
            );

            foreach ($data['reviews'] ?? [] as $r) {
                $text = trim($r['text']['text'] ?? $r['originalText']['text'] ?? '');
                if ($text === '') continue;
                $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);

                $reviewTime = isset($r['publishTime'])
                    ? date('Y-m-d H:i:s', strtotime($r['publishTime']))
                    : null;

                $author = $r['authorAttribution']['displayName'] ?? null;
                $photoUrl = $r['authorAttribution']['photoUri'] ?? null;

                GoogleReview::firstOrCreate(
                    [
                        'google_place_id' => $placeId,
                        'author_name' => $author,
                        'review_time' => $reviewTime,
                    ],
                    [
                        'profile_photo_url' => $photoUrl,
                        'rating' => $r['rating'] ?? 0,
                        'text' => $text,
                    ]
                );
            }

            return $cache;
        } catch (\Throwable $e) {
            Log::warning('GoogleReviewService fetch error', [
                'place_id' => $placeId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function matchPlaceId(string $name, ?float $lat, ?float $lng, ?string $address = null): ?string
    {
        if (!$this->apiKey) return null;

        $query = $address ? trim($address . ' ' . $name) : $name;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.location',
            ])->timeout(6)->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $query,
                'languageCode' => 'ko',
                'maxResultCount' => 5,
            ]);

            if (!$response->successful()) return null;

            $places = $response->json()['places'] ?? [];
            if (empty($places)) return null;

            $best = null;
            $bestDist = INF;
            $normName = $this->normalizeName($name);

            foreach ($places as $p) {
                $pName = $this->normalizeName($p['displayName']['text'] ?? '');
                if (!str_contains($pName, $normName) && !str_contains($normName, $pName)) {
                    continue;
                }

                if ($lat && $lng && isset($p['location'])) {
                    $dist = $this->haversineDist(
                        $lat, $lng,
                        $p['location']['latitude'] ?? 0,
                        $p['location']['longitude'] ?? 0
                    );
                    if ($dist < $bestDist) {
                        $bestDist = $dist;
                        $best = $p;
                    }
                } elseif (!$best) {
                    $best = $p;
                }
            }

            if ($best && ($bestDist <= 1000 || !$lat)) {
                $rawId = $best['id'] ?? null;
                if ($rawId && str_starts_with($rawId, 'places/')) {
                    $rawId = substr($rawId, 7);
                }
                return $rawId;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('GoogleReviewService matchPlaceId error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getReviewData(string $placeId): ?array
    {
        $cache = GooglePlaceCache::where('google_place_id', $placeId)->first();
        if (!$cache || $cache->review_count < 5) return null;

        $reviews = GoogleReview::where('google_place_id', $placeId)
            ->whereNotNull('text')
            ->orderByDesc('review_time')
            ->limit(3)
            ->get();

        if ($reviews->isEmpty()) return null;

        $featured = $reviews->first(fn($r) => $r->rating >= 4) ?? $reviews->first();

        return [
            'rating' => (float) $cache->rating,
            'review_count' => (int) $cache->review_count,
            'place_id' => $placeId,
            'featured' => $featured ? [
                'author' => $featured->author_name,
                'rating' => (int) $featured->rating,
                'text' => $featured->text,
                'time' => $featured->review_time?->format('Y-m-d'),
            ] : null,
            'reviews' => $reviews->map(fn($r) => [
                'author' => $r->author_name,
                'rating' => (int) $r->rating,
                'text' => $r->text,
                'time' => $r->review_time?->format('Y-m-d'),
            ])->toArray(),
        ];
    }

    public static function getReviewDataBulk(array $placeIds): array
    {
        $placeIds = array_filter(array_unique($placeIds));
        if (empty($placeIds)) return [];

        $caches = GooglePlaceCache::whereIn('google_place_id', $placeIds)
            ->where('review_count', '>=', 5)
            ->get()
            ->keyBy('google_place_id');

        if ($caches->isEmpty()) return [];

        $reviews = GoogleReview::whereIn('google_place_id', $caches->keys())
            ->whereNotNull('text')
            ->orderByDesc('review_time')
            ->get()
            ->groupBy('google_place_id');

        $result = [];
        foreach ($caches as $pid => $cache) {
            $pReviews = ($reviews[$pid] ?? collect())->take(3);
            if ($pReviews->isEmpty()) continue;

            $featured = $pReviews->first(fn($r) => $r->rating >= 4) ?? $pReviews->first();

            $result[$pid] = [
                'rating' => (float) $cache->rating,
                'review_count' => (int) $cache->review_count,
                'place_id' => $pid,
                'featured' => $featured ? [
                    'author' => $featured->author_name,
                    'rating' => (int) $featured->rating,
                    'text' => $featured->text,
                    'time' => $featured->review_time?->format('Y-m-d'),
                ] : null,
                'reviews' => $pReviews->map(fn($r) => [
                    'author' => $r->author_name,
                    'rating' => (int) $r->rating,
                    'text' => $r->text,
                    'time' => $r->review_time?->format('Y-m-d'),
                ])->values()->toArray(),
            ];
        }

        return $result;
    }

    private function normalizeName(string $s): string
    {
        $s = preg_replace('/\s+/u', '', $s);
        $s = preg_replace('/[\p{P}\p{S}]/u', '', $s);
        return mb_strtolower((string) $s);
    }

    private function haversineDist(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $R * asin(min(1.0, sqrt($a)));
    }
}
