<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PublicPlaceController extends Controller
{
    public function show(Place $place)
    {
        if (!$place->is_public) abort(404);
        $place->load(['category', 'images', 'themes']);

        $canonicalQuery = $this->canonicalQuery($place);

        $now = Carbon::now();
        $weekAgo = $now->copy()->subDays(7);
        $twoWeeksAgo = $now->copy()->subDays(14);

        $totalSaves = (clone $canonicalQuery)->count();
        $weeklySaves = (clone $canonicalQuery)->where('created_at', '>=', $weekAgo)->count();
        $prevWeekSaves = (clone $canonicalQuery)
            ->whereBetween('created_at', [$twoWeeksAgo, $weekAgo])->count();

        $growthRate = $prevWeekSaves > 0
            ? ($weeklySaves - $prevWeekSaves) / max($prevWeekSaves, 1)
            : ($weeklySaves > 0 ? 1.0 : 0);
        $isHot = $growthRate >= 0.3 && $weeklySaves >= 3;

        $regions = $this->aggregateRegions($canonicalQuery);
        $topRegion = $regions->first();

        $themes = $this->aggregateThemes($canonicalQuery);
        $topTheme = $themes->first();

        $categoryRank = $this->categoryRank($place, $weeklySaves);

        $insights = [];
        if ($isHot) {
            $insights[] = [
                'title' => '이번주 급상승 저장 장소',
                'desc' => '최근 7일 동안 저장 수가 빠르게 늘어난 장소예요.',
            ];
        }
        if ($topTheme && $topTheme->ratio >= 0.5) {
            $insights[] = [
                'title' => $topTheme->name . ' 테마 저장 비중이 높아요',
                'desc' => '사람들이 주로 \'' . $topTheme->name . '\' 느낌으로 저장하고 있어요.',
            ];
        }
        if ($topRegion && $topRegion->ratio >= 0.4) {
            $insights[] = [
                'title' => $topRegion->name . ' 지역 저장이 많아요',
                'desc' => $topRegion->name . '에서 저장 빈도가 높은 장소로 집계되고 있어요.',
            ];
        }
        $insights = array_slice($insights, 0, 3);

        $memos = (clone $canonicalQuery)
            ->where('is_public', 1)
            ->whereNotNull('memo')
            ->where('memo', '!=', '')
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('memo');

        $similarPlaces = $this->similarPlaces($place, $canonicalQuery);

        $heroImage = null;
        if ($place->images->isNotEmpty()) {
            $heroImage = $place->images->first()->url;
        } elseif ($place->thumbnail) {
            $heroImage = asset('storage/' . $place->thumbnail);
        }

        return view('public-place.show', [
            'place' => $place,
            'heroImage' => $heroImage,
            'totalSaves' => $totalSaves,
            'weeklySaves' => $weeklySaves,
            'isHot' => $isHot,
            'insights' => $insights,
            'topRegion' => $topRegion,
            'topTheme' => $topTheme,
            'themes' => $themes->take(3),
            'categoryRank' => $categoryRank,
            'memos' => $memos,
            'similarPlaces' => $similarPlaces,
            'naverClientId' => config('services.naver_map.client_id'),
            'googleMapsKey' => config('services.google_maps.api_key'),
        ]);
    }

    private function canonicalQuery(Place $place)
    {
        $q = Place::query();
        if (!empty($place->kakao_place_id)) {
            $q->where('kakao_place_id', $place->kakao_place_id);
        } else {
            $q->whereRaw('ROUND(lat, 5) = ?', [round((float)$place->lat, 5)])
              ->whereRaw('ROUND(lng, 5) = ?', [round((float)$place->lng, 5)]);
        }
        return $q->where('is_visible', 1);
    }

    private function aggregateRegions($canonicalQuery)
    {
        $rows = (clone $canonicalQuery)->get(['address', 'road_address', 'is_overseas']);
        $counts = [];
        foreach ($rows as $r) {
            $region = $this->regionOf($r);
            if (!$region) continue;
            $counts[$region] = ($counts[$region] ?? 0) + 1;
        }
        $total = array_sum($counts);
        arsort($counts);
        $result = collect();
        foreach ($counts as $name => $c) {
            $result->push((object)[
                'name' => $name,
                'count' => $c,
                'ratio' => $total > 0 ? $c / $total : 0,
            ]);
        }
        return $result;
    }

    private function regionOf($p): string
    {
        $addr = (string)($p->road_address ?: ($p->address ?: ''));
        if (!$addr) return '';
        if ($p->is_overseas) {
            if (str_contains($addr, ',')) {
                $parts = array_map('trim', explode(',', $addr));
                return end($parts) ?: '';
            }
            return $addr;
        }
        $first = explode(' ', trim($addr))[0] ?? '';
        $map = [
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
        return $map[$first] ?? $first;
    }

    private function aggregateThemes($canonicalQuery)
    {
        $ids = (clone $canonicalQuery)->pluck('id');
        if ($ids->isEmpty()) return collect();
        $rows = DB::table('place_themes')
            ->join('themes', 'themes.id', '=', 'place_themes.theme_id')
            ->whereIn('place_themes.place_id', $ids)
            ->select('themes.id', 'themes.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('themes.id', 'themes.name')
            ->orderByDesc('cnt')
            ->get();
        $total = $rows->sum('cnt');
        return $rows->map(fn($r) => (object)[
            'id' => $r->id,
            'name' => $r->name,
            'count' => (int)$r->cnt,
            'ratio' => $total > 0 ? $r->cnt / $total : 0,
        ]);
    }

    private function categoryRank(Place $place, int $weeklySaves): ?int
    {
        if (!$place->category_id || $weeklySaves === 0 || !$place->kakao_place_id) return null;
        $weekAgo = Carbon::now()->subDays(7);
        $rows = DB::table('places')
            ->join('categories', 'categories.id', '=', 'places.category_id')
            ->where('categories.name', $place->category?->name)
            ->where('places.is_visible', 1)
            ->where('places.created_at', '>=', $weekAgo)
            ->whereNotNull('places.kakao_place_id')
            ->select('places.kakao_place_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('places.kakao_place_id')
            ->orderByDesc('cnt')
            ->limit(50)
            ->get();
        if ($rows->isEmpty()) return null;
        $rank = 1;
        foreach ($rows as $r) {
            if ($r->kakao_place_id === $place->kakao_place_id) return $rank;
            $rank++;
        }
        return null;
    }

    private function similarPlaces(Place $place, $canonicalQuery)
    {
        $userIds = (clone $canonicalQuery)->pluck('user_id')->unique();
        if ($userIds->isEmpty()) return collect();

        $rows = DB::table('places')
            ->whereIn('user_id', $userIds)
            ->where('is_visible', 1)
            ->where('is_public', 1)
            ->whereNotNull('kakao_place_id')
            ->where('kakao_place_id', '!=', $place->kakao_place_id ?? '')
            ->select('kakao_place_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('kakao_place_id')
            ->orderByDesc('cnt')
            ->limit(4)
            ->pluck('kakao_place_id');

        if ($rows->isEmpty()) return collect();

        return Place::with(['category', 'images'])
            ->whereIn('kakao_place_id', $rows)
            ->where('is_visible', 1)
            ->where('is_public', 1)
            ->get()
            ->groupBy('kakao_place_id')
            ->map(fn($group) => $group->first())
            ->values();
    }

    // 다른 사람 장소를 내 계정에 복사 저장
    public function copyToMine(Place $place, Request $request)
    {
        if (!$place->is_public) abort(404);
        if (!$request->user()) return response()->json(['error' => 'unauthenticated'], 401);

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $user = $request->user();
        $category = \App\Models\Category::where('id', $data['category_id'])
            ->where('user_id', $user->id)->firstOrFail();

        $new = $place->replicate(['thumbnail', 'sort_order']);
        $new->user_id = $user->id;
        $new->category_id = $category->id;
        $new->is_visible = true;
        $new->is_public = false;
        $new->sort_order = 0;
        $new->status = 'planned';
        $new->visited_at = null;
        $new->save();

        return response()->json([
            'ok' => true,
            'place_id' => $new->id,
            'redirect' => route('places.show', $new),
        ]);
    }
}
