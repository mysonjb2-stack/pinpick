<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TrendingController extends Controller
{
    /**
     * 트렌딩 장소 집계 API
     * GET /api/places/trending
     *   ?period=7days|30days  (기본 7days)
     *   ?theme={slug}         (선택) — food/cafe/travel/beauty/medical/stay/shopping/culture/etc
     *   ?region={지역명}       (선택) — 시/도 or 시/구 LIKE 매칭
     *   ?group_by=region      (선택) — 지역별 대표 1개 (여행자 핀픽용)
     *   ?limit=10             (기본 10, 최대 30)
     *
     * canonical 그룹핑:
     *   kakao_place_id 우선 → naver_place_id → (ROUND(lat,4), ROUND(lng,4)) ≈ 11m 버킷
     */
    public function trending(Request $request)
    {
        $period = $request->input('period', '7days');
        $days = $period === '30days' ? 30 : 7;
        $themeSlug = $request->input('theme');
        $region = $request->input('region');
        $groupBy = $request->input('group_by');
        $scope = $request->input('scope'); // domestic | overseas (travel 전용)
        $limit = min(max((int) $request->input('limit', 10), 1), 30);

        $since = Carbon::now()->subDays($days);
        $weekAgo = Carbon::now()->subDays(7);

        $q = Place::query()
            ->where('is_public', 1)
            ->where('is_visible', 1);

        if ($themeSlug) {
            $themeId = Theme::where('slug', $themeSlug)->value('id');
            if (!$themeId) return response()->json(['items' => []]);
            $q->whereHas('themes', fn($t) => $t->where('themes.id', $themeId));
        }

        if ($scope === 'domestic') {
            $q->where('is_overseas', 0);
        } elseif ($scope === 'overseas') {
            $q->where('is_overseas', 1);
        }

        if ($region) {
            $q->where(function ($w) use ($region) {
                $w->where('address', 'like', "%{$region}%")
                  ->orWhere('road_address', 'like', "%{$region}%");
            });
        }

        $places = (clone $q)->with(['themes', 'images', 'category'])->get();
        if ($places->isEmpty()) return response()->json(['items' => []]);

        $groups = $places->groupBy(fn($p) => $this->canonicalKey($p));

        $items = collect();
        foreach ($groups as $key => $groupPlaces) {
            $rep = $groupPlaces
                ->sortByDesc(fn($p) => ($p->images->count() > 0 ? 1 : 0) * 1e12 + $p->created_at->timestamp)
                ->first();

            $totalCount = $groupPlaces->count();
            $weekCount = $groupPlaces->filter(fn($p) => $p->created_at >= $weekAgo)->count();
            $periodCount = ($days === 7)
                ? $weekCount
                : $groupPlaces->filter(fn($p) => $p->created_at >= $since)->count();

            if ($periodCount === 0) continue;

            $themeCounts = $groupPlaces->flatMap(fn($p) => $p->themes->pluck('name'))->countBy();
            $topTheme = $themeCounts->isNotEmpty() ? $themeCounts->sortDesc()->keys()->first() : null;

            $items->push([
                'id' => $rep->id,
                'name' => $rep->name,
                'address' => $rep->road_address ?: $rep->address,
                'region' => $this->regionOf($rep),
                'thumbnail' => $this->thumbnailOf($rep),
                'theme_name' => $topTheme,
                'category_name' => $rep->category?->name,
                'week_count' => $weekCount,
                'period_count' => $periodCount,
                'total_count' => $totalCount,
            ]);
        }

        if ($items->isEmpty()) return response()->json(['items' => []]);

        if ($groupBy === 'region') {
            $regionGroups = $items->filter(fn($i) => !empty($i['region']))->groupBy('region');
            $result = collect();
            foreach ($regionGroups as $regionName => $regionItems) {
                $total = $regionItems->sum('period_count');
                $rep = $regionItems->sortByDesc('period_count')->first();
                $rep['region'] = $regionName;
                $rep['region_count'] = $total;
                $result->push($rep);
            }
            $result = $result->sortByDesc('region_count')->take($limit)->values();
            return response()->json(['items' => $result]);
        }

        $items = $items->sortByDesc('period_count')->take($limit)->values();
        return response()->json(['items' => $items]);
    }

    private function canonicalKey(Place $p): string
    {
        if (!empty($p->kakao_place_id)) return 'k:' . $p->kakao_place_id;
        if (!empty($p->naver_place_id)) return 'n:' . $p->naver_place_id;
        return 'c:' . round((float) $p->lat, 4) . ',' . round((float) $p->lng, 4);
    }

    /**
     * 전체보기 페이지네이션 API
     * GET /api/places/trending/list
     *   ?type=weekly|local|theme|travel  (필수)
     *   ?theme={slug}                    (type=theme 일 때)
     *   ?region={지역명}                  (type=local 일 때)
     *   ?sort=popular|recent             (기본 popular)
     *   ?page=1                          (기본 1)
     *   ?limit=20                        (기본 20, 최대 50)
     */
    public function list(Request $request)
    {
        $type = $request->input('type', 'weekly');
        $themeSlug = $request->input('theme');
        $region = $request->input('region');
        $scope = $request->input('scope'); // travel: domestic | overseas
        $sort = $request->input('sort', 'popular') === 'recent' ? 'recent' : 'popular';
        $page = max((int) $request->input('page', 1), 1);
        $limit = min(max((int) $request->input('limit', 20), 1), 50);

        // 타입별 기본값/필터
        $days = 30;
        if ($type === 'weekly') $days = 7;
        if ($type === 'travel') {
            $days = 7;
            $themeSlug = 'travel';
            // travel 은 scope 없으면 기본 domestic
            if (!in_array($scope, ['domestic', 'overseas'], true)) $scope = 'domestic';
        }
        $since = Carbon::now()->subDays($days);
        $weekAgo = Carbon::now()->subDays(7);

        $q = Place::query()
            ->where('is_public', 1)
            ->where('is_visible', 1);

        $themeId = null;
        if ($themeSlug) {
            $themeId = Theme::where('slug', $themeSlug)->value('id');
            if (!$themeId) {
                return response()->json([
                    'items' => [], 'page' => $page, 'has_more' => false, 'total_groups' => 0,
                    'regions' => [],
                ]);
            }
            $q->whereHas('themes', fn($t) => $t->where('themes.id', $themeId));
        }

        if ($type === 'travel') {
            $q->where('is_overseas', $scope === 'overseas' ? 1 : 0);
        }

        if ($region && in_array($type, ['local', 'travel'], true)) {
            $q->where(function ($w) use ($region) {
                $w->where('address', 'like', "%{$region}%")
                  ->orWhere('road_address', 'like', "%{$region}%");
            });
        }

        $places = (clone $q)->with(['themes', 'images', 'category'])->get();

        if ($places->isEmpty()) {
            return response()->json([
                'items' => [], 'page' => $page, 'has_more' => false, 'total_groups' => 0,
            ]);
        }

        $groups = $places->groupBy(fn($p) => $this->canonicalKey($p));

        // 현재 사용자의 canonical key 셋 (is_mine 표시용)
        $myKeys = collect();
        $myStatusMap = collect();
        $user = $request->user();
        if ($user) {
            $myPlaces = Place::where('user_id', $user->id)
                ->where('is_visible', 1)
                ->get();
            foreach ($myPlaces as $mp) {
                $key = $this->canonicalKey($mp);
                $myKeys->push($key);
                // status 우선순위: visited > planned
                $cur = $myStatusMap->get($key);
                if ($cur !== 'visited') {
                    $myStatusMap[$key] = $mp->status ?: ($mp->visited_at ? 'visited' : 'planned');
                }
            }
            $myKeys = $myKeys->unique();
        }

        $items = collect();
        foreach ($groups as $key => $groupPlaces) {
            $rep = $groupPlaces
                ->sortByDesc(fn($p) => ($p->images->count() > 0 ? 1 : 0) * 1e12 + $p->created_at->timestamp)
                ->first();

            $totalCount = $groupPlaces->count();
            $weekCount = $groupPlaces->filter(fn($p) => $p->created_at >= $weekAgo)->count();
            $periodCount = ($days === 7)
                ? $weekCount
                : $groupPlaces->filter(fn($p) => $p->created_at >= $since)->count();

            // weekly/travel 은 7일 내 저장이 있어야 함
            if (in_array($type, ['weekly', 'travel'], true) && $periodCount === 0) continue;

            $themeCounts = $groupPlaces->flatMap(fn($p) => $p->themes->pluck('name'))->countBy();
            $topTheme = $themeCounts->isNotEmpty() ? $themeCounts->sortDesc()->keys()->first() : null;

            $isMine = $myKeys->contains($key);
            $mineStatus = $isMine ? ($myStatusMap[$key] ?? null) : null;

            $items->push([
                'id' => $rep->id,
                'name' => $rep->name,
                'address' => $this->shortAddress($rep),
                'region' => $this->regionOf($rep),
                'thumbnail' => $this->thumbnailOf($rep),
                'theme_name' => $topTheme,
                'category_name' => $rep->category?->name,
                'week_count' => $weekCount,
                'period_count' => $periodCount,
                'total_count' => $totalCount,
                'is_overseas' => (bool) $rep->is_overseas,
                'visited_at' => $rep->visited_at?->format('Y.m.d'),
                'created_at' => $rep->created_at?->format('Y.m.d'),
                'is_mine' => $isMine,
                'mine_status' => $mineStatus,
                '_sort_recent' => $rep->created_at->timestamp,
                '_canonical' => $key,
            ]);
        }

        $totalGroups = $items->count();

        // 정렬
        if ($sort === 'recent') {
            $items = $items->sortByDesc('_sort_recent')->values();
        } else {
            $items = $items->sortByDesc(fn($i) => $i['period_count'] * 1e9 + $i['_sort_recent'])->values();
        }

        // 페이지네이션
        $offset = ($page - 1) * $limit;
        $sliced = $items->slice($offset, $limit)->values();
        $hasMore = $totalGroups > $offset + $limit;

        // 내부 정렬 키 제거
        $sliced = $sliced->map(function ($it) {
            unset($it['_sort_recent']);
            return $it;
        });

        // travel 타입은 page=1 응답에 지역(도시/나라) 탭 목록을 포함
        $regions = [];
        if ($type === 'travel' && $page === 1) {
            $regions = $this->travelRegions($themeId, $scope, $weekAgo);
        }

        return response()->json([
            'items' => $sliced,
            'page' => $page,
            'has_more' => $hasMore,
            'total_groups' => $totalGroups,
            'regions' => $regions,
            'scope' => $scope,
        ]);
    }

    /**
     * 여행 테마의 지역(도시/나라) 탭 목록을 저장 빈도 순으로 추출.
     *   scope=domestic → 시/도 (서울, 경기, ...)
     *   scope=overseas → 나라 (일본, 베트남, ...)
     */
    private function travelRegions(?int $themeId, string $scope, Carbon $weekAgo): array
    {
        if (!$themeId) return [];
        $q = Place::query()
            ->where('is_public', 1)
            ->where('is_visible', 1)
            ->where('is_overseas', $scope === 'overseas' ? 1 : 0)
            ->where('created_at', '>=', $weekAgo)
            ->whereHas('themes', fn($t) => $t->where('themes.id', $themeId));
        $rows = $q->get(['address', 'road_address', 'is_overseas']);
        $counts = [];
        foreach ($rows as $r) {
            $name = self::regionOfStatic(
                (string) ($r->road_address ?: ($r->address ?: '')),
                (bool) $r->is_overseas
            );
            if (!$name) continue;
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
        arsort($counts);
        return array_slice(array_keys($counts), 0, 12);
    }

    /**
     * 주소 축약 — 국내: 시/도 + 시/구 + 동(앞 3토큰), 해외: 마지막 1~2 토큰
     */
    private function shortAddress(Place $p): string
    {
        $addr = (string) ($p->road_address ?: ($p->address ?: ''));
        if (!$addr) return '';
        if ($p->is_overseas) {
            if (str_contains($addr, ',')) {
                $parts = array_map('trim', explode(',', $addr));
                $tail = array_slice($parts, -2);
                return implode(', ', $tail);
            }
            return $addr;
        }
        $parts = preg_split('/\s+/', trim($addr));
        $head = array_slice($parts, 0, 3);
        return implode(' ', $head);
    }

    private function thumbnailOf(Place $p): ?string
    {
        if ($p->images->isNotEmpty()) {
            $first = $p->images->first();
            return $first->thumb_url ?? $first->url ?? null;
        }
        if ($p->thumbnail) {
            // 시드/구버전 더미 경로 대비 — 실제 파일 존재 시에만 URL 반환
            $abs = storage_path('app/public/' . ltrim($p->thumbnail, '/'));
            if (is_file($abs)) {
                return asset('storage/' . $p->thumbnail);
            }
        }
        // 사진 없음 — 좌표 있으면 정적 지도로 fallback
        if ($p->lat && $p->lng) {
            return url('/api/static-map?' . http_build_query([
                'lat' => $p->lat,
                'lng' => $p->lng,
                'overseas' => $p->is_overseas ? 1 : 0,
                'w' => 400,
                'h' => 400,
            ]));
        }
        return null;
    }

    /**
     * 주소에서 시/도 단위 지역명 추출 (PublicPlaceController와 동일 로직)
     */
    public static function regionOfStatic(string $address, bool $isOverseas = false): string
    {
        if (!$address) return '';
        if ($isOverseas) {
            if (str_contains($address, ',')) {
                $parts = array_map('trim', explode(',', $address));
                $last = end($parts) ?: '';
                // 우편번호 prefix 제거: "540-0002 일본" → "일본", "94103 USA" → "USA"
                $cleaned = preg_replace('/^[\d\-]+\s+/u', '', $last);
                $cleaned = trim($cleaned ?: '');
                return $cleaned !== '' ? $cleaned : $last;
            }
            return $address;
        }
        $first = explode(' ', trim($address))[0] ?? '';
        $map = self::regionMap();
        return $map[$first] ?? $first;
    }

    private function regionOf(Place $p): string
    {
        $addr = (string) ($p->road_address ?: ($p->address ?: ''));
        return self::regionOfStatic($addr, (bool) $p->is_overseas);
    }

    public static function regionMap(): array
    {
        return [
            '서울특별시' => '서울', '서울시' => '서울', '서울' => '서울',
            '부산광역시' => '부산', '부산시' => '부산', '부산' => '부산',
            '대구광역시' => '대구', '대구시' => '대구', '대구' => '대구',
            '인천광역시' => '인천', '인천시' => '인천', '인천' => '인천',
            '광주광역시' => '광주', '광주시' => '광주',
            '대전광역시' => '대전', '대전시' => '대전', '대전' => '대전',
            '울산광역시' => '울산', '울산시' => '울산', '울산' => '울산',
            '세종특별자치시' => '세종', '세종시' => '세종', '세종' => '세종',
            '경기도' => '경기', '경기' => '경기',
            '강원도' => '강원', '강원특별자치도' => '강원',
            '충청북도' => '충북', '충북' => '충북',
            '충청남도' => '충남', '충남' => '충남',
            '전라북도' => '전북', '전북특별자치도' => '전북',
            '전라남도' => '전남', '전남' => '전남',
            '경상북도' => '경북', '경북' => '경북',
            '경상남도' => '경남', '경남' => '경남',
            '제주특별자치도' => '제주', '제주도' => '제주', '제주' => '제주',
        ];
    }
}
