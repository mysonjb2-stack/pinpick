<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 장소명 + 좌표 + 주소 조합으로 네이버 플레이스 ID를 찾아준다.
 * - 네이버 로컬 검색 API (https://openapi.naver.com/v1/search/local.json)
 * - 결과 중 300m 이내 항목만 채택, 가장 가까운 것 선택 (동명 오매칭 방지)
 * - Redis 30일 캐시 (키: naver_match:md5(name+lat+lng))
 * - link 필드 → NaverUrlParser로 place_id 추출
 * - 실패/에러 모두 null 반환 (호출자에게 예외 던지지 않음)
 */
class NaverPlaceMatcher
{
    private const ENDPOINT = 'https://openapi.naver.com/v1/search/local.json';
    private const MATCH_RADIUS_M = 300;
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 30; // 30일
    private const HTTP_TIMEOUT = 3;

    public function __construct(private NaverUrlParser $urlParser) {}

    public function match(string $name, ?float $lat, ?float $lng, ?string $address = null): ?string
    {
        $name = trim($name);
        if ($name === '' || $lat === null || $lng === null) {
            return null;
        }

        // 국내 좌표 범위 밖은 즉시 종료
        if ($lat < 33.0 || $lat > 39.0 || $lng < 124.0 || $lng > 132.0) {
            return null;
        }

        $clientId = config('services.naver_search.client_id');
        $clientSecret = config('services.naver_search.client_secret');
        if (!$clientId || !$clientSecret) {
            return null;
        }

        $cacheKey = $this->cacheKey($name, $lat, $lng);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 'NO_MATCH' ? null : (string) $cached;
        }

        try {
            $query = $this->buildQuery($name, $address);
            $response = Http::withHeaders([
                'X-Naver-Client-Id' => $clientId,
                'X-Naver-Client-Secret' => $clientSecret,
            ])->timeout(self::HTTP_TIMEOUT)->get(self::ENDPOINT, [
                'query' => $query,
                'display' => 5,
                'start' => 1,
                'sort' => 'random',
            ]);

            if (!$response->successful()) {
                Log::info('naver_search failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return null;
            }

            $items = $response->json('items') ?? [];
            $best = $this->pickClosest($items, $lat, $lng);

            if ($best === null) {
                Cache::put($cacheKey, 'NO_MATCH', self::CACHE_TTL_SECONDS);
                return null;
            }

            $placeId = $this->urlParser->extractPlaceId($best['link'] ?? '');
            if (!$placeId) {
                Cache::put($cacheKey, 'NO_MATCH', self::CACHE_TTL_SECONDS);
                return null;
            }

            Cache::put($cacheKey, $placeId, self::CACHE_TTL_SECONDS);
            return $placeId;
        } catch (\Throwable $e) {
            Log::warning('naver_match exception: ' . $e->getMessage(), [
                'name' => $name,
                'lat' => $lat,
                'lng' => $lng,
            ]);
            return null;
        }
    }

    private function cacheKey(string $name, float $lat, float $lng): string
    {
        return 'naver_match:' . md5(mb_strtolower($name) . '|' . round($lat, 5) . '|' . round($lng, 5));
    }

    private function buildQuery(string $name, ?string $address): string
    {
        if ($address) {
            $parts = preg_split('/\s+/u', trim($address));
            if (is_array($parts) && count($parts) >= 2) {
                return $name . ' ' . $parts[0] . ' ' . $parts[1];
            }
        }
        return $name;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function pickClosest(array $items, float $lat, float $lng): ?array
    {
        $best = null;
        $bestDist = INF;
        foreach ($items as $item) {
            $coord = $this->parseCoord($item['mapx'] ?? null, $item['mapy'] ?? null);
            if ($coord === null) continue;
            [$itemLng, $itemLat] = $coord;
            $dist = $this->haversine($lat, $lng, $itemLat, $itemLng);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $item;
            }
        }
        if ($best === null || $bestDist > self::MATCH_RADIUS_M) {
            return null;
        }
        return $best;
    }

    /**
     * 네이버 로컬 검색 결과의 mapx/mapy는 경/위도 * 10^7 (예: "1272430547" = 127.2430547).
     * 구버전 KATEC 포맷(6자리 이하 정수값)도 방어적으로 허용.
     * @return array{0: float, 1: float}|null  [lng, lat]
     */
    private function parseCoord($mapx, $mapy): ?array
    {
        if ($mapx === null || $mapy === null) return null;
        $x = (float) $mapx;
        $y = (float) $mapy;
        if ($x <= 0 || $y <= 0) return null;

        if ($x > 1_000_000) {
            return [$x / 1e7, $y / 1e7];
        }
        if ($x > 100 && $x < 200 && $y > 20 && $y < 50) {
            return [$x, $y];
        }
        return null;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $R * asin(min(1.0, sqrt($a)));
    }
}
