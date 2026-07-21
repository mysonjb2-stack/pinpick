<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Models\Place;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CurationController extends Controller
{
    public function show(int $id)
    {
        $curation = Curation::where('id', $id)
            ->published()
            ->firstOrFail();

        $viewKey = "curation_view:{$curation->id}:" . (request()->ip() ?? 'unknown');
        if (!Cache::has($viewKey)) {
            $curation->increment('view_count');
            Cache::put($viewKey, true, 300);
        }

        $curation->load('places');

        $userCategories = [];
        if (Auth::check()) {
            $userCategories = Category::where('user_id', Auth::id())
                ->orderBy('sort_order')
                ->get(['id', 'name', 'icon']);
        }

        return view('curations.show', compact('curation', 'userCategories'));
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
            Category::where('user_id', $user->id)->increment('sort_order');
            $category = Category::create([
                'user_id' => $user->id,
                'name' => $request->new_category_name,
                'icon' => '📌',
                'sort_order' => 0,
            ]);
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

            Place::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $cp->place_name,
                'original_name' => $cp->place_name,
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
            ]);

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
        $region = $request->get('region');
        $curations = Curation::published()
            ->withCount('places')
            ->when($region, fn($q) => $q->where('region_label', 'like', "%{$region}%"))
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['id', 'title', 'slug', 'type', 'description', 'cover_image',
                    'region_label', 'save_count', 'published_at']);

        $curations->each(function ($c) {
            $c->cover_url = $c->cover_image ? asset('storage/' . $c->cover_image) : null;
        });

        return response()->json($curations);
    }

    public function apiRegions()
    {
        $labels = Curation::published()
            ->whereNotNull('region_label')
            ->pluck('region_label');

        $counts = [];
        foreach ($labels as $label) {
            foreach (array_map('trim', explode(',', $label)) as $tag) {
                if ($tag === '') continue;
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        $regions = collect($counts)
            ->filter(fn($c) => $c >= 1)
            ->keys()
            ->sort()
            ->values();

        return response()->json($regions);
    }
}
