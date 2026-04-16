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

    private function thumbnailOf(Place $p): ?string
    {
        if ($p->images->isNotEmpty()) {
            $first = $p->images->first();
            return $first->thumb_url ?? $first->url ?? null;
        }
        if ($p->thumbnail) {
            return asset('storage/' . $p->thumbnail);
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
                return end($parts) ?: '';
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
