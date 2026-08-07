<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Theme;
use App\Services\AddressParserService;
use App\Services\GoogleReviewService;
use App\Services\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CurationController extends Controller
{
    public function show(int $id)
    {
        $curation = Curation::where('id', $id)
            ->where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'pending')->whereNotNull('approved_snapshot');
                  });
            })
            ->firstOrFail();

        $viewKey = "curation_view:{$curation->id}:" . (request()->ip() ?? 'unknown');
        if (!Cache::has($viewKey)) {
            $curation->increment('view_count');
            Cache::put($viewKey, true, 300);
        }

        $curation->load(['places', 'author']);

        $isBlockedAuthor = false;
        if (Auth::check() && $curation->author_user_id) {
            $isBlockedAuthor = \App\Models\BlockedUser::where('user_id', Auth::id())
                ->where('blocked_user_id', $curation->author_user_id)
                ->exists();
        }

        if ($curation->status === 'pending' && $curation->approved_snapshot) {
            $snap = $curation->approved_snapshot;
            $curation->title = $snap['title'] ?? $curation->title;
            $curation->description = $snap['description'] ?? $curation->description;
            $curation->category = $snap['category'] ?? $curation->category;
            $curation->region_label = $snap['region_label'] ?? $curation->region_label;
            $curation->cover_image = $snap['cover_image'] ?? $curation->cover_image;

            if (!empty($snap['places'])) {
                $snapPlaces = collect($snap['places'])->map(fn($p) => new CurationPlace($p));
                $curation->setRelation('places', $snapPlaces);
            }
        }

        $userCategories = [];
        if (Auth::check()) {
            $userCategories = Category::where('user_id', Auth::id())
                ->orderBy('sort_order')
                ->get(['id', 'name', 'icon']);
        }

        $googlePlaceIds = $curation->places
            ->pluck('google_place_id')
            ->filter()
            ->toArray();
        $googleReviews = GoogleReviewService::getReviewDataBulk($googlePlaceIds);

        return view('curations.show', compact('curation', 'userCategories', 'googleReviews', 'isBlockedAuthor'));
    }

    public function saveToMyPinpick(Request $request, int $id)
    {
        $curation = Curation::where('id', $id)->published()->firstOrFail();

        $request->validate([
            'place_ids' => 'required|array|min:1',
            'place_ids.*' => 'integer|exists:curation_places,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'new_category_name' => 'nullable|string|max:30',
        ]);

        $user = Auth::user();

        if ($request->new_category_name) {
            $category = Category::where('user_id', $user->id)
                ->where('name', $request->new_category_name)
                ->first();
            if (!$category) {
                Category::where('user_id', $user->id)->increment('sort_order');
                $category = Category::create([
                    'user_id' => $user->id,
                    'name' => $request->new_category_name,
                    'icon' => '📌',
                    'sort_order' => 0,
                ]);
            }
        } elseif ($request->category_id) {
            $category = Category::where('id', $request->category_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            return response()->json(['error' => '카테고리를 선택해주세요.'], 422);
        }

        $curPlaces = CurationPlace::where('curation_id', $curation->id)
            ->whereIn('id', $request->place_ids)
            ->get();

        $existingExtIds = Place::where('user_id', $user->id)
            ->whereNotNull('kakao_place_id')
            ->pluck('kakao_place_id')
            ->merge(
                Place::where('user_id', $user->id)
                    ->whereNotNull('naver_place_id')
                    ->pluck('naver_place_id')
            )
            ->toArray();

        $maxSort = Place::where('user_id', $user->id)->max('sort_order') ?? -1;
        $saved = 0;
        $skipped = 0;

        $koreaProvinces = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];
        $isCourse = $curation->type === 'course';

        foreach ($curPlaces as $cp) {
            $isDup = ($cp->external_place_id && in_array($cp->external_place_id, $existingExtIds))
                || ($cp->naver_place_id && in_array($cp->naver_place_id, $existingExtIds));
            if ($isDup) {
                $skipped++;
                continue;
            }

            $isOverseas = (bool) $cp->is_overseas;
            if (!$isOverseas && $cp->address) {
                $isOverseas = true;
                foreach ($koreaProvinces as $prov) {
                    if (str_starts_with($cp->address, $prov)) {
                        $isOverseas = false;
                        break;
                    }
                }
            }

            $memo = '';
            if ($isCourse && $cp->day_number) {
                $memo = "Day {$cp->day_number}";
            }

            $newPlace = Place::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $cp->place_name,
                'original_name' => $cp->place_name,
                'address' => $cp->jibeon_address,
                'road_address' => $cp->address,
                'building_name' => $cp->building_name,
                'phone' => $cp->phone,
                'opening_hours' => $cp->opening_hours,
                'lat' => $cp->latitude,
                'lng' => $cp->longitude,
                'memo' => $memo,
                'status' => 'planned',
                'is_overseas' => $isOverseas,
                'is_public' => false,
                'sort_order' => ++$maxSort,
                'kakao_place_id' => $cp->external_place_id,
                'naver_place_id' => $cp->naver_place_id,
                'google_place_id' => $cp->google_place_id,
                'address_raw' => $cp->address_raw,
                'country_code' => $cp->country_code,
                'region_l1' => $cp->region_l1,
                'region_l2' => $cp->region_l2,
                'region_l1_key' => $cp->region_l1_key,
                'region_l2_key' => $cp->region_l2_key,
            ]);

            if (!empty($cp->photos)) {
                foreach ($cp->photos as $i => $photoPath) {
                    if (Storage::disk('public')->exists($photoPath)) {
                        PlaceImage::create([
                            'place_id' => $newPlace->id,
                            'path' => $photoPath,
                            'sort_order' => $i,
                        ]);
                    }
                }
                $firstPhoto = $cp->photos[0];
                $thumbPath = ImageProcessor::thumbPathFor($firstPhoto);
                if (Storage::disk('public')->exists($thumbPath)) {
                    $newPlace->update(['thumbnail' => $thumbPath]);
                } elseif (Storage::disk('public')->exists($firstPhoto)) {
                    $newPlace->update(['thumbnail' => $firstPhoto]);
                }
            }

            $saved++;
        }

        if ($saved > 0) {
            $curation->increment('save_count');
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'skipped' => $skipped,
        ]);
    }

    public function apiList(Request $request)
    {
        $blockedIds = Auth::check() ? Auth::user()->blockedUserIds() : [];

        $category = $request->get('category');
        $curations = Curation::where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(fn($q2) => $q2->where('status', 'pending')->whereNotNull('approved_snapshot'));
            })
            ->when(!empty($blockedIds), fn($q) => $q->where(function ($q2) use ($blockedIds) {
                $q2->whereNull('author_user_id')
                   ->orWhereNotIn('author_user_id', $blockedIds);
            }))
            ->with(['places' => fn($q) => $q->orderBy('sort_order')->limit(6), 'author:id,name,profile_image'])
            ->withCount('places')
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['id', 'title', 'slug', 'type', 'nights', 'days', 'category', 'description',
                    'cover_image', 'save_count', 'published_at',
                    'author_type', 'author_user_id', 'status', 'approved_snapshot',
                    'center_lat', 'center_lng', 'region_codes', 'region_label']);

        $savedIds = [];
        if (Auth::check()) {
            $savedIds = Place::where('user_id', Auth::id())
                ->whereNotNull('kakao_place_id')
                ->pluck('kakao_place_id')
                ->merge(
                    Place::where('user_id', Auth::id())
                        ->whereNotNull('naver_place_id')
                        ->pluck('naver_place_id')
                )
                ->toArray();
        }

        $googleTypeLabels = config('google_type_labels');
        $addressParser = app(AddressParserService::class);

        $curations->each(function ($c) use ($savedIds, $googleTypeLabels, $addressParser) {
            if ($c->status === 'pending' && $c->approved_snapshot) {
                $snap = $c->approved_snapshot;
                $c->title = $snap['title'] ?? $c->title;
                $c->description = $snap['description'] ?? $c->description;
                $c->category = $snap['category'] ?? $c->category;
                $c->cover_image = $snap['cover_image'] ?? $c->cover_image;
                if (!empty($snap['places'])) {
                    $snapPlaces = collect($snap['places'])->map(fn($p) => new CurationPlace($p));
                    $c->setRelation('places', $snapPlaces);
                    $c->places_count = count($snap['places']);
                }
            }

            $coverUrl = $c->cover_image ? asset('storage/' . $c->cover_image) : null;
            $c->cover_url = $coverUrl;
            $catConfig = config("curation_categories.{$c->category}");
            $c->category_label = $catConfig['label'] ?? $c->category;

            $hasSaved = false;
            $c->places_list = $c->places->map(function ($p) use (&$hasSaved, $savedIds, $googleTypeLabels, $addressParser) {
                $thumb = $p->thumb_url;
                if (!empty($savedIds)) {
                    if (($p->external_place_id && in_array($p->external_place_id, $savedIds))
                        || ($p->naver_place_id && in_array($p->naver_place_id, $savedIds))) {
                        $hasSaved = true;
                    }
                }

                if ($p->is_overseas) {
                    $koAlias = $addressParser->regionL1KoAlias($p->country_code, $p->region_l1_key);
                    $region = $koAlias ?: $p->region_l1 ?: ($p->country_code ? $addressParser->countryCodeToName($p->country_code) : '');
                    $catLabel = $this->resolveGoogleTypeLabel($p->category_label, $googleTypeLabels);
                } else {
                    $region = $p->region_l1 ?: '';
                    $catLabel = $p->category_label ?: '';
                }

                return [
                    'id' => $p->id,
                    'name' => $p->place_name,
                    'address' => $p->address,
                    'category_label' => $catLabel,
                    'thumb_url' => $thumb,
                    'region' => $region,
                    'lat' => $p->latitude,
                    'lng' => $p->longitude,
                    'is_overseas' => (bool) $p->is_overseas,
                ];
            });
            $c->is_saved = $hasSaved;
            $c->author_name = $c->author_type === 'user' && $c->author
                ? $c->author->name : '핀픽';
            $c->author_avatar = $c->author_type === 'user' && $c->author && $c->author->profile_image
                ? $c->author->profile_image : null;
            $c->is_official = $c->author_type === 'admin';
            $c->author_hue = $c->author_name ? crc32($c->author_name) % 360 : 0;

            $catConfig = config("curation_categories.{$c->category}");
            $catIcon = $catConfig['icon'] ?? '';
            $catLabel = $catConfig['label'] ?? '';
            $c->cat_badge = $catLabel ? ($catIcon . $catLabel) : '';

            $badges = $this->buildRegionBadgesFromPlaces($c->places, $addressParser);
            if (empty($badges) || (count($badges) === 1 && $badges[0] === '해외')) {
                $badges = $this->parseRegionLabelToBadges($c->region_label);
            }
            $c->region_badges = $badges;

            $durationLabel = '';
            if ($c->type === 'course' && $c->nights !== null && $c->days !== null) {
                if ($c->nights == 0 && $c->days == 1) {
                    $durationLabel = '당일치기';
                } elseif ($c->nights == 0) {
                    $durationLabel = '무박 ' . $c->days . '일';
                } else {
                    $durationLabel = $c->nights . '박 ' . $c->days . '일';
                }
            }
            $c->duration_badge = $durationLabel;

            unset($c->places, $c->author, $c->status, $c->approved_snapshot,
                  $c->region_codes, $c->region_label, $c->nights, $c->days);
            $c->makeVisible('author_user_id');
        });

        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        if ($lat && $lng) {
            $curations->each(function ($c) use ($lat, $lng) {
                if ($c->center_lat && $c->center_lng) {
                    $c->nearby_dist = $this->haversineKm($lat, $lng, (float) $c->center_lat, (float) $c->center_lng);
                } else {
                    $c->nearby_dist = 99999;
                }
                $c->is_nearby = $c->nearby_dist <= 50;
                unset($c->center_lat, $c->center_lng);
            });
            $near = $curations->filter(fn($c) => $c->is_nearby)->sortBy('nearby_dist')->values();
            $far = $curations->filter(fn($c) => !$c->is_nearby)->values();
            $curations = $near->merge($far);
        } else {
            $curations->each(function ($c) {
                unset($c->center_lat, $c->center_lng);
            });
        }

        return response()->json($curations);
    }

    private function resolveGoogleTypeLabel(?string $rawType, array $map): string
    {
        if (!$rawType) return '';
        $types = array_map('trim', explode(',', $rawType));
        $best = '';
        foreach ($types as $t) {
            if (!isset($map[$t])) continue;
            if ($map[$t] === null) continue;
            $best = $map[$t];
            break;
        }
        return $best;
    }

    private function parseRegionLabelToBadges(?string $regionLabel): array
    {
        if (!$regionLabel) return [];
        $parts = array_map('trim', explode(',', $regionLabel));
        return array_values(array_filter(array_slice($parts, 0, 2)));
    }

    private function buildRegionBadgesFromPlaces($places, AddressParserService $parser): array
    {
        if ($places->isEmpty()) return [];

        $isOverseas = $places->contains(fn($p) => $p->is_overseas);
        $countryCode = $places->first(fn($p) => $p->is_overseas && $p->country_code)?->country_code;

        if ($isOverseas) {
            $countryName = $countryCode ? $parser->countryCodeToName($countryCode) : '';
            $l1Groups = [];
            foreach ($places as $p) {
                if (!$p->is_overseas) continue;
                $key = $p->region_l1_key ?: ($p->region_l1 ?: '');
                if (!$key) continue;
                $koAlias = $parser->regionL1KoAlias($p->country_code, $p->region_l1_key);
                $display = $koAlias ?: $p->region_l1 ?: $key;
                if (!isset($l1Groups[$key])) {
                    $l1Groups[$key] = ['display' => $display, 'count' => 0];
                }
                $l1Groups[$key]['count']++;
            }

            if (empty($l1Groups)) return $countryName ? [$countryName] : ['해외'];

            $firstL1 = reset($l1Groups)['display'];
            if (count($l1Groups) === 1 && $countryName && mb_strtolower($countryName) === mb_strtolower($firstL1)) {
                return [$countryName];
            }

            if (count($l1Groups) === 1) {
                return [$countryName, $firstL1];
            }

            uasort($l1Groups, fn($a, $b) => $b['count'] <=> $a['count']);
            $top = array_values($l1Groups)[0]['display'];
            $rest = count($l1Groups) - 1;
            return [$countryName, $top . ' 외 ' . $rest];
        }

        // 국내
        $l1Groups = [];
        $l2Groups = [];
        foreach ($places as $p) {
            $l1Key = $p->region_l1_key ?: ($p->region_l1 ?: '');
            $l2Key = $p->region_l2_key ?: ($p->region_l2 ?: '');
            $l1Display = $p->region_l1 ?: $l1Key;
            $l2Display = $p->region_l2 ?: $l2Key;

            if ($l1Key) {
                if (!isset($l1Groups[$l1Key])) {
                    $l1Groups[$l1Key] = ['display' => $l1Display, 'count' => 0];
                }
                $l1Groups[$l1Key]['count']++;
            }
            if ($l2Key) {
                $ck = $l1Key . '/' . $l2Key;
                if (!isset($l2Groups[$ck])) {
                    $l2Groups[$ck] = ['display' => $l2Display, 'count' => 0];
                }
                $l2Groups[$ck]['count']++;
            }
        }

        if (empty($l1Groups)) return [];

        if (count($l1Groups) === 1) {
            $l1Display = reset($l1Groups)['display'];
            if (empty($l2Groups)) return [$l1Display];

            uasort($l2Groups, fn($a, $b) => $b['count'] <=> $a['count']);
            $l2Vals = array_values($l2Groups);
            if (count($l2Vals) === 1) return [$l1Display, $l2Vals[0]['display']];
            return [$l1Display, $l2Vals[0]['display'] . ' 외 ' . (count($l2Vals) - 1)];
        }

        uasort($l1Groups, fn($a, $b) => $b['count'] <=> $a['count']);
        $l1Vals = array_values($l1Groups);
        if (count($l1Vals) === 2) return [$l1Vals[0]['display'], $l1Vals[1]['display']];
        return [$l1Vals[0]['display'], $l1Vals[1]['display'] . ' 외 ' . (count($l1Vals) - 2)];
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function getGlobalPool(?int $userId = null): array
    {
        $globalPool = Cache::remember('cur_nearby:global:pool:v2', 600, function () {
            $rows = DB::select("
                SELECT cp.id, cp.curation_id, cp.place_name, cp.thumbnail_url,
                       cp.latitude, cp.longitude, cp.is_overseas,
                       cp.external_place_id, cp.naver_place_id,
                       cp.address, cp.country_code, cp.region_l1, cp.region_l1_key,
                       c.save_count AS cur_save_count,
                       c.author_user_id
                FROM curation_places cp
                JOIN curations c ON c.id = cp.curation_id
                WHERE c.status = 'approved'
            ");
            return collect($rows)->map(fn($r) => (array) $r)->values()->toArray();
        });

        $globalPool = collect($globalPool);
        if ($globalPool->isEmpty()) return [];

        if ($userId) {
            $blockedIds = \App\Models\BlockedUser::where('user_id', $userId)
                ->pluck('blocked_user_id')->toArray();
            if (!empty($blockedIds)) {
                $globalPool = $globalPool->reject(fn($p) =>
                    $p['author_user_id'] && in_array($p['author_user_id'], $blockedIds)
                );
            }
        }

        if ($userId) {
            $savedExtIds = Place::where('user_id', $userId)
                ->whereNotNull('kakao_place_id')
                ->pluck('kakao_place_id')
                ->merge(
                    Place::where('user_id', $userId)
                        ->whereNotNull('naver_place_id')
                        ->pluck('naver_place_id')
                )
                ->toArray();

            if (!empty($savedExtIds)) {
                $globalPool = $globalPool->reject(fn($p) =>
                    ($p['external_place_id'] && in_array($p['external_place_id'], $savedExtIds))
                    || ($p['naver_place_id'] && in_array($p['naver_place_id'], $savedExtIds))
                );
            }
        }

        $deduped = collect();
        $seenExt = [];
        $globalPool->sortByDesc('cur_save_count')->each(function ($p) use (&$deduped, &$seenExt) {
            $extId = $p['external_place_id'] ?: null;
            if ($extId && isset($seenExt[$extId])) return;
            if ($extId) $seenExt[$extId] = true;
            $deduped->push($p);
        });
        $globalPool = $deduped;

        $withPhoto = $globalPool->filter(fn($p) => !empty($p['thumbnail_url']))->values();
        $noPhoto = $globalPool->filter(fn($p) => empty($p['thumbnail_url']))->values();
        $maxPick = 12;
        if ($withPhoto->count() >= $maxPick) {
            $pick = $withPhoto->random($maxPick);
        } else {
            $pick = $withPhoto;
            $need = $maxPick - $pick->count();
            if ($noPhoto->count() > 0) {
                $pick = $pick->merge($noPhoto->random(min($need, $noPhoto->count())));
            }
        }

        $self = new self();
        return $pick->sortByDesc('cur_save_count')->values()->map(fn($p) => [
            'id' => $p['id'],
            'curation_id' => $p['curation_id'],
            'name' => $p['place_name'],
            'thumb_url' => $p['thumbnail_url'],
            'lat' => $p['latitude'],
            'lng' => $p['longitude'],
            'is_overseas' => (bool) $p['is_overseas'],
            'distance' => null,
            'region_label' => $self->buildRegionLabel($p),
        ])->values()->toArray();
    }

    public function apiNearby(Request $request)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        $isLocationless = !$lat && !$lng;

        $cacheKey = $isLocationless ? 'cur_nearby:fallback' : ('cur_nearby:' . round($lat, 2) . ':' . round($lng, 2));

        $savedExtIds = [];
        $blockedIds = [];
        if (Auth::check()) {
            $savedExtIds = Place::where('user_id', Auth::id())
                ->whereNotNull('kakao_place_id')
                ->pluck('kakao_place_id')
                ->merge(
                    Place::where('user_id', Auth::id())
                        ->whereNotNull('naver_place_id')
                        ->pluck('naver_place_id')
                )
                ->toArray();
            $blockedIds = Auth::user()->blockedUserIds();
        }

        $isFallback = false;
        $pool = collect();

        if (!$isLocationless) {
            $pool = Cache::remember($cacheKey . ':pool:v2', 600, function () use ($lat, $lng) {
                $found = collect();
                foreach ([5000, 10000] as $radius) {
                    $rows = DB::select("
                        SELECT cp.id, cp.curation_id, cp.place_name, cp.thumbnail_url,
                               cp.latitude, cp.longitude, cp.is_overseas,
                               cp.external_place_id, cp.naver_place_id,
                               cp.dong_label, cp.country_code, cp.region_l1, cp.region_l1_key,
                               c.save_count AS cur_save_count,
                               c.author_user_id,
                               (6371000 * acos(LEAST(1, cos(radians(?)) * cos(radians(cp.latitude))
                                * cos(radians(cp.longitude) - radians(?))
                                + sin(radians(?)) * sin(radians(cp.latitude))))) AS dist
                        FROM curation_places cp
                        JOIN curations c ON c.id = cp.curation_id
                        WHERE c.status = 'approved'
                          AND cp.latitude != 0 AND cp.longitude != 0
                        HAVING dist <= ?
                        ORDER BY dist
                    ", [$lat, $lng, $lat, $radius]);

                    $found = collect($rows);
                    if ($found->count() >= 3) break;
                }
                return $found->map(fn($r) => (array) $r)->values()->toArray();
            });

            $pool = collect($pool);

            if (!empty($blockedIds)) {
                $pool = $pool->reject(fn($p) =>
                    ($p['author_user_id'] ?? null) && in_array($p['author_user_id'], $blockedIds)
                );
            }

            if (!empty($savedExtIds)) {
                $pool = $pool->reject(function ($p) use ($savedExtIds) {
                    return ($p['external_place_id'] && in_array($p['external_place_id'], $savedExtIds))
                        || ($p['naver_place_id'] && in_array($p['naver_place_id'], $savedExtIds));
                });
            }

            // 동일 장소 중복 제거 (external_place_id 기준, 담기 수 많은 리스트 소속 우선)
            $deduped = collect();
            $seenExt = [];
            $pool->sortByDesc('cur_save_count')->each(function ($p) use (&$deduped, &$seenExt) {
                $extId = $p['external_place_id'] ?: null;
                if ($extId && isset($seenExt[$extId])) return;
                if ($extId) $seenExt[$extId] = true;
                $deduped->push($p);
            });
            $pool = $deduped;

            if ($pool->count() < 3) {
                $isFallback = true;
                $pool = collect();
            }
        }

        if ($isLocationless || $isFallback) {
            $globalPool = Cache::remember('cur_nearby:global:pool:v2', 600, function () {
                $rows = DB::select("
                    SELECT cp.id, cp.curation_id, cp.place_name, cp.thumbnail_url,
                           cp.latitude, cp.longitude, cp.is_overseas,
                           cp.external_place_id, cp.naver_place_id,
                           cp.address, cp.country_code, cp.region_l1, cp.region_l1_key,
                           c.save_count AS cur_save_count
                    FROM curation_places cp
                    JOIN curations c ON c.id = cp.curation_id
                    WHERE c.status = 'approved'
                ");
                return collect($rows)->map(fn($r) => (array) $r)->values()->toArray();
            });

            $globalPool = collect($globalPool);
            if ($globalPool->isEmpty()) {
                return response()->json(['data' => null]);
            }

            if (!empty($savedExtIds)) {
                $globalPool = $globalPool->reject(fn($p) =>
                    ($p['external_place_id'] && in_array($p['external_place_id'], $savedExtIds))
                    || ($p['naver_place_id'] && in_array($p['naver_place_id'], $savedExtIds))
                );
            }

            $deduped = collect();
            $seenExt = [];
            $globalPool->sortByDesc('cur_save_count')->each(function ($p) use (&$deduped, &$seenExt) {
                $extId = $p['external_place_id'] ?: null;
                if ($extId && isset($seenExt[$extId])) return;
                if ($extId) $seenExt[$extId] = true;
                $deduped->push($p);
            });
            $globalPool = $deduped;

            $withPhoto = $globalPool->filter(fn($p) => !empty($p['thumbnail_url']))->values();
            $noPhoto = $globalPool->filter(fn($p) => empty($p['thumbnail_url']))->values();
            $maxPick = 12;
            $pick = collect();
            if ($withPhoto->count() >= $maxPick) {
                $pick = $withPhoto->random($maxPick);
            } else {
                $pick = $withPhoto;
                $need = $maxPick - $pick->count();
                if ($noPhoto->count() > 0) {
                    $pick = $pick->merge($noPhoto->random(min($need, $noPhoto->count())));
                }
            }

            $toMap = fn($p) => [
                'id' => $p['id'],
                'curation_id' => $p['curation_id'],
                'name' => $p['place_name'],
                'thumb_url' => $p['thumbnail_url'],
                'lat' => $p['latitude'],
                'lng' => $p['longitude'],
                'is_overseas' => (bool) $p['is_overseas'],
                'distance' => null,
                'region_label' => $this->buildRegionLabel($p),
            ];

            $places = $pick->sortByDesc('cur_save_count')->values()->map($toMap)->values();

            return response()->json([
                'data' => [
                    'places' => $places,
                    'region' => '',
                    'is_fallback' => false,
                    'is_global' => true,
                    'pool_size' => $globalPool->count(),
                ]
            ]);
        }

        // 사진 있는 장소 우선, 없는 장소는 뒤로
        $withPhoto = $pool->filter(fn($p) => !empty($p['thumbnail_url']))->values();
        $noPhoto = $pool->filter(fn($p) => empty($p['thumbnail_url']))->values();

        $maxPick = 12;
        $pick = collect();
        if ($withPhoto->count() >= $maxPick) {
            $pick = $withPhoto->random($maxPick);
        } else {
            $pick = $withPhoto;
            $need = $maxPick - $pick->count();
            if ($noPhoto->count() > 0) {
                $pick = $pick->merge($noPhoto->random(min($need, $noPhoto->count())));
            }
        }

        $toMap = fn($p) => [
            'id' => $p['id'],
            'curation_id' => $p['curation_id'],
            'name' => $p['place_name'],
            'thumb_url' => $p['thumbnail_url'],
            'lat' => $p['latitude'],
            'lng' => $p['longitude'],
            'is_overseas' => (bool) $p['is_overseas'],
            // 해외는 거리 대신 지역 라벨 (사용자가 해외에 있어 주변 풀에 해외가 들어온 경우)
            'distance' => $p['is_overseas'] ? null : round($p['dist'], 1),
            'dong' => $p['is_overseas'] ? null : ($p['dong_label'] ?? null),
            'region_label' => $p['is_overseas'] ? $this->buildRegionLabel($p) : null,
        ];

        $places = $pick->sortBy('dist')->values()->map($toMap)->values();

        $region = Cache::remember(
            $cacheKey . ':region', 600,
            function () use ($lat, $lng) {
                $key = config('services.kakao_local.rest_api_key');
                if (!$key) return '';
                $res = Http::withHeaders(['Authorization' => 'KakaoAK ' . $key])
                    ->get('https://dapi.kakao.com/v2/local/geo/coord2regioncode.json', ['x' => $lng, 'y' => $lat]);
                $doc = collect($res->json('documents', []))->firstWhere('region_type', 'H');
                if (!$doc) return '';
                $parts = explode(' ', $doc['address_name']);
                $dong = end($parts);
                $dong = preg_replace('/\d+(동)$/', '$1', $dong);
                $name = preg_replace('/(동|읍|면)$/', '', $dong);
                if ($name !== '') return $name;
                $gu = count($parts) >= 2 ? $parts[count($parts) - 2] : '';
                return preg_replace('/(구|시|군)$/', '', $gu) ?: $dong;
            }
        );

        return response()->json([
            'data' => [
                'places' => $places->values(),
                'region' => $region,
                'is_fallback' => false,
                'pool_size' => $pool->count(),
            ]
        ]);
    }

    /**
     * 추천 카드 서브라벨.
     * 국내: "{시도} {시군구}" (주소 파싱)
     * 해외: "{한글 국가명} {region_l1}" — 국가명과 region_l1이 같거나(도시국가)
     *       region_l1이 비면 국가명만. 거리는 표시하지 않는다.
     */
    private function buildRegionLabel(array $p): ?string
    {
        if (empty($p['is_overseas'])) {
            return $this->parseRegionLabel($p['address'] ?? '');
        }

        $cc = $p['country_code'] ?? null;
        // 미분류(NULL) 또는 KR 오분류 해외 건은 라벨 생략 — region:backfill 대상
        if (!$cc || $cc === 'KR') return null;

        $parser = app(AddressParserService::class);
        $country = $parser->countryCodeToName($cc);

        // 도시국가는 region_l1이 국가명과 같아 "싱가포르 싱가포르"가 된다
        if ($parser->isCityState($cc)) return $country;

        $l1Key = $p['region_l1_key'] ?? null;
        $l1 = $parser->regionL1KoAlias($cc, $l1Key)
            ?? (self::overseasL1DisplayMap()[$cc . '|' . $l1Key] ?? null)
            ?? trim((string) ($p['region_l1'] ?? ''));

        if ($l1 === '' || $l1 === $country) return $country;

        return $country . ' ' . $l1;
    }

    /**
     * (country_code|region_l1_key) → 대표 표기.
     * Google의 ko 응답이 지역마다 한글/현지어로 뒤섞여 들어오므로
     * 같은 키에 한글 표기가 하나라도 있으면 그것을, 없으면 최다 표기를 쓴다.
     */
    private static function overseasL1DisplayMap(): array
    {
        return Cache::remember('cur_region:l1_display:v1', 600, function () {
            $rows = DB::select("
                SELECT country_code, region_l1_key, region_l1, COUNT(*) AS n
                FROM curation_places
                WHERE is_overseas = 1
                  AND country_code IS NOT NULL AND country_code <> 'KR'
                  AND region_l1_key IS NOT NULL AND region_l1 <> ''
                GROUP BY country_code, region_l1_key, region_l1
            ");

            $best = [];
            foreach ($rows as $r) {
                $key = $r->country_code . '|' . $r->region_l1_key;
                $cand = [
                    'label' => $r->region_l1,
                    'ko' => (bool) preg_match('/\p{Hangul}/u', $r->region_l1),
                    'n' => (int) $r->n,
                ];
                $cur = $best[$key] ?? null;
                if (!$cur
                    || ($cand['ko'] && !$cur['ko'])
                    || ($cand['ko'] === $cur['ko'] && $cand['n'] > $cur['n'])) {
                    $best[$key] = $cand;
                }
            }

            return array_map(fn($v) => $v['label'], $best);
        });
    }

    private function parseRegionLabel(string $address): ?string
    {
        if (!$address) return null;

        $parts = preg_split('/\s+/', trim($address));
        if (count($parts) < 2) return null;

        $provinceMap = [
            '서울특별시' => '서울', '서울' => '서울',
            '부산광역시' => '부산', '부산' => '부산',
            '대구광역시' => '대구', '대구' => '대구',
            '인천광역시' => '인천', '인천' => '인천',
            '광주광역시' => '광주', '광주' => '광주',
            '대전광역시' => '대전', '대전' => '대전',
            '울산광역시' => '울산', '울산' => '울산',
            '세종특별자치시' => '세종', '세종' => '세종',
            '경기도' => '경기', '경기' => '경기',
            '강원특별자치도' => '강원', '강원도' => '강원', '강원' => '강원',
            '충청북도' => '충북', '충북' => '충북',
            '충청남도' => '충남', '충남' => '충남',
            '전북특별자치도' => '전북', '전라북도' => '전북', '전북' => '전북',
            '전라남도' => '전남', '전남' => '전남',
            '경상북도' => '경북', '경북' => '경북',
            '경상남도' => '경남', '경남' => '경남',
            '제주특별자치도' => '제주', '제주도' => '제주', '제주' => '제주',
        ];

        $metro = ['서울', '부산', '대구', '인천', '광주', '대전', '울산'];

        $prov = $provinceMap[$parts[0]] ?? null;
        if (!$prov) return null;

        if ($prov === '세종') return '세종';

        $second = $parts[1] ?? '';
        if (!$second) return $prov;

        if (in_array($prov, $metro)) {
            if (str_ends_with($second, '구')) return $prov . ' ' . $second;
            return $prov;
        }

        if (str_ends_with($second, '시')) {
            $city = mb_substr($second, 0, -1);
            if ($city === $prov) return $prov;
            return $prov . ' ' . $city;
        }
        if (str_ends_with($second, '군')) {
            $county = mb_substr($second, 0, -1);
            return $prov . ' ' . $county;
        }

        return $prov;
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function apiCategories()
    {
        $usedCategories = Curation::where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(fn($q2) => $q2->where('status', 'pending')->whereNotNull('approved_snapshot'));
            })
            ->select('category')
            ->groupBy('category')
            ->pluck('category')
            ->toArray();

        $all = config('curation_categories');
        $result = [];
        foreach ($all as $slug => $cat) {
            if (in_array($slug, $usedCategories)) {
                $result[] = ['slug' => $slug, 'label' => $cat['label'], 'icon' => $cat['icon']];
            }
        }

        return response()->json($result);
    }
}
