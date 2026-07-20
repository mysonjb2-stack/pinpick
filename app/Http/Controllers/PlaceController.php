<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Theme;
use App\Services\ImageProcessor;
use App\Services\NaverPlaceMatcher;
use App\Services\NaverUrlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use function Illuminate\Support\defer;

class PlaceController extends Controller
{
    public function create(Request $request)
    {
        if ($request->user()) {
            CategoryController::ensureUserCategories($request->user());
            $categories = Category::where('user_id', $request->user()->id)
                ->orderBy('sort_order')->get();
        } else {
            $categories = Category::whereNull('user_id')->orderBy('sort_order')->get();
        }
        $naverClientId = config('services.naver_map.client_id');
        $googleMapsKey = config('services.google_maps.api_key');
        $themes = Theme::orderBy('sort_order')->get(['id', 'name', 'slug']);
        return view('places.create', compact('categories', 'naverClientId', 'googleMapsKey', 'themes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'opening_hours' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'road_address' => ['nullable', 'string', 'max:255'],
            'building_name' => ['nullable', 'string', 'max:100'],
            'detail_location' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'memo' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
            'kakao_place_id' => ['nullable', 'string'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'naver_url' => ['nullable', 'string', 'max:500'],
            'is_overseas' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
            'theme_ids' => ['nullable', 'array', 'max:2'],
            'theme_ids.*' => ['integer', 'exists:themes,id'],
            'original_name' => ['nullable', 'string', 'max:255'],
        ]);

        $themeIds = $data['theme_ids'] ?? [];
        unset($data['theme_ids']);

        // naver_url → naver_place_id 파싱
        $naverUrlInput = $data['naver_url'] ?? null;
        unset($data['naver_url']);
        if ($naverUrlInput) {
            $parsed = app(NaverUrlParser::class)->extractPlaceId($naverUrlInput);
            if ($parsed) {
                $data['naver_place_id'] = $parsed;
                $data['naver_matched_at'] = now();
            }
        }

        if (!empty($data['opening_hours'])) {
            $data['opening_hours'] = json_decode($data['opening_hours'], true);
        } else {
            $data['opening_hours'] = null;
        }

        $data['is_overseas'] = (bool) ($data['is_overseas'] ?? false);
        $data['user_id'] = $request->user()->id;
        // personal-only-v1: 공개 장소 노출 차단 — 항상 비공개로 강제
        $data['is_public'] = false;

        // 좌표가 비어있으면 주소로 forward geocoding
        if (empty($data['lat']) || empty($data['lng'])) {
            $addr = $data['road_address'] ?? $data['address'] ?? '';
            if ($addr !== '') {
                [$lat, $lng] = $this->forwardGeocode($addr, $data['is_overseas']);
                $data['lat'] = $lat;
                $data['lng'] = $lng;
            }
        }

        $images = $request->file('images', []);
        unset($data['images']);

        $place = Place::create($data);
        $place->themes()->sync($themeIds);

        $processor = app(ImageProcessor::class);
        foreach ($images as $i => $file) {
            try {
                $path = $processor->processPlaceImage($file, 'places/' . $place->id);
            } catch (\Throwable $e) {
                Log::warning('image process failed: ' . $e->getMessage(), ['place_id' => $place->id]);
                continue;
            }
            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }

        $placeId = $place->id;
        defer(function () use ($placeId) {
            $p = Place::find($placeId);
            if (!$p) return;
            $this->generateMapThumbnail($p);
            $this->tryMatchNaverPlaceId($p);
        });

        return redirect('/')->with('success', '장소가 저장되었어요.');
    }

    public function show(Place $place)
    {
        abort_unless($place->user_id === request()->user()?->id, 403);
        $place->load(['images', 'themes', 'category']);
        $naverClientId = config('services.naver_map.client_id');
        $googleMapsKey = config('services.google_maps.api_key');
        return view('places.show', compact('place', 'naverClientId', 'googleMapsKey'));
    }

    public function showGuest(string $localId)
    {
        return view('places.guest-show', [
            'localId' => $localId,
            'naverClientId' => config('services.naver_map.client_id'),
            'googleMapsKey' => config('services.google_maps.api_key'),
        ]);
    }

    public function importGuest(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'places' => ['required', 'array', 'min:1', 'max:20'],
            'places.*.name' => ['required', 'string', 'max:255'],
            'places.*.original_name' => ['nullable', 'string', 'max:255'],
            'places.*.category_name' => ['nullable', 'string', 'max:255'],
            'places.*.phone' => ['nullable', 'string', 'max:50'],
            'places.*.opening_hours' => ['nullable'],
            'places.*.address' => ['nullable', 'string', 'max:255'],
            'places.*.road_address' => ['nullable', 'string', 'max:255'],
            'places.*.lat' => ['nullable', 'numeric'],
            'places.*.lng' => ['nullable', 'numeric'],
            'places.*.memo' => ['nullable', 'string', 'max:500'],
            'places.*.status' => ['nullable', 'in:planned,visited'],
            'places.*.visited_at' => ['nullable'],
            'places.*.is_overseas' => ['nullable', 'boolean'],
            'places.*.building_name' => ['nullable', 'string', 'max:255'],
            'places.*.detail_location' => ['nullable', 'string', 'max:255'],
            'places.*.themes' => ['nullable', 'array'],
            'places.*.themes.*' => ['string', 'max:30'],
            'places.*.kakao_place_id' => ['nullable', 'string', 'max:100'],
            'places.*.naver_place_id' => ['nullable', 'string', 'max:100'],
            'places.*.google_place_id' => ['nullable', 'string', 'max:100'],
            'places.*.thumbnail_url' => ['nullable', 'string', 'max:500'],
            'places.*._category_id' => ['nullable', 'integer'],
            'places.*._new_category' => ['nullable', 'string', 'max:30'],
        ]);

        CategoryController::ensureUserCategories($user);
        $userCats = Category::where('user_id', $user->id)->get();
        $catByName = $userCats->keyBy(fn ($c) => mb_strtolower($c->name));
        $catById = $userCats->keyBy('id');
        $newCatCache = [];

        $maxSort = (int) Place::where('user_id', $user->id)->max('sort_order');
        $imported = 0;

        $newCatNames = collect($data['places'])
            ->filter(fn ($p) => !empty($p['_new_category']) && !isset($catByName[mb_strtolower($p['_new_category'])]))
            ->pluck('_new_category')
            ->map(fn ($n) => mb_strtolower($n))
            ->unique()
            ->values();

        if ($newCatNames->isNotEmpty()) {
            Category::where('user_id', $user->id)
                ->increment('sort_order', $newCatNames->count());
        }
        $newCatSortCounter = 0;

        foreach ($data['places'] as $p) {
            $catId = null;

            if (!empty($p['_category_id']) && isset($catById[$p['_category_id']])) {
                $catId = (int) $p['_category_id'];
            } elseif (!empty($p['_new_category'])) {
                $newName = $p['_new_category'];
                $lower = mb_strtolower($newName);
                if (isset($catByName[$lower])) {
                    $catId = $catByName[$lower]->id;
                } elseif (isset($newCatCache[$lower])) {
                    $catId = $newCatCache[$lower];
                } else {
                    $cat = Category::create([
                        'user_id' => $user->id,
                        'name' => $newName,
                        'icon' => '📌',
                        'sort_order' => $newCatSortCounter++,
                    ]);
                    $newCatCache[$lower] = $cat->id;
                    $catId = $cat->id;
                }
            } else {
                $catName = isset($p['category_name']) ? mb_strtolower($p['category_name']) : '';
                $catId = $catByName[$catName]->id ?? null;
            }

            $lat = isset($p['lat']) && $p['lat'] !== '' ? (float) $p['lat'] : null;
            $lng = isset($p['lng']) && $p['lng'] !== '' ? (float) $p['lng'] : null;
            $visited = !empty($p['visited_at']) ? substr($p['visited_at'], 0, 10) : null;

            $openingHours = $p['opening_hours'] ?? null;
            if (is_string($openingHours)) {
                $openingHours = json_decode($openingHours, true);
            }

            $roadAddr = $p['road_address'] ?? null;
            $addr = $p['address'] ?? null;
            if ($addr && $roadAddr && $addr === $roadAddr) {
                $addr = null;
            }

            $newPlace = Place::create([
                'user_id' => $user->id,
                'category_id' => $catId,
                'name' => $p['name'],
                'original_name' => $p['original_name'] ?? null,
                'phone' => $p['phone'] ?? null,
                'opening_hours' => $openingHours,
                'address' => $addr,
                'road_address' => $roadAddr,
                'building_name' => $p['building_name'] ?? null,
                'detail_location' => $p['detail_location'] ?? null,
                'lat' => $lat,
                'lng' => $lng,
                'memo' => $p['memo'] ?? null,
                'status' => $p['status'] ?? 'planned',
                'visited_at' => $visited,
                'is_overseas' => !empty($p['is_overseas']),
                'kakao_place_id' => $p['kakao_place_id'] ?? null,
                'naver_place_id' => $p['naver_place_id'] ?? null,
                'google_place_id' => $p['google_place_id'] ?? null,
                'sort_order' => ++$maxSort,
                'is_visible' => true,
                'is_public' => false,
            ]);

            if (!empty($p['themes'])) {
                $themeIds = Theme::whereIn('name', $p['themes'])->pluck('id');
                if ($themeIds->isNotEmpty()) {
                    $newPlace->themes()->sync($themeIds);
                }
            }

            if (!empty($p['thumbnail_url'])) {
                $this->importGuestThumbnail($p['thumbnail_url'], $newPlace);
            }

            $imported++;
        }

        return response()->json(['ok' => true, 'imported' => $imported]);
    }

    private function importGuestThumbnail(string $url, Place $place): void
    {
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) return;

        $storagePath = str_replace('/storage/', '', $parsed);
        if (!Storage::disk('public')->exists($storagePath)) return;

        $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'webp';
        $destDir = "places/{$place->id}";
        Storage::disk('public')->makeDirectory($destDir);
        $destPath = "{$destDir}/" . \Illuminate\Support\Str::random(40) . ".{$ext}";

        Storage::disk('public')->copy($storagePath, $destPath);

        PlaceImage::create([
            'place_id' => $place->id,
            'path' => $destPath,
            'sort_order' => 0,
        ]);

        app(ImageProcessor::class)->generateThumbFrom($destPath);
        $thumbPath = ImageProcessor::thumbPathFor($destPath);
        $place->update(['thumbnail' => $thumbPath]);
    }

    public function edit(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);
        $place->load(['images', 'themes']);

        CategoryController::ensureUserCategories($request->user());
        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('sort_order')->get();
        $naverClientId = config('services.naver_map.client_id');
        $googleMapsKey = config('services.google_maps.api_key');
        $themes = Theme::orderBy('sort_order')->get(['id', 'name', 'slug']);

        return view('places.create', compact('place', 'categories', 'naverClientId', 'googleMapsKey', 'themes'));
    }

    public function update(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'opening_hours' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'road_address' => ['nullable', 'string', 'max:255'],
            'building_name' => ['nullable', 'string', 'max:100'],
            'detail_location' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'memo' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
            'kakao_place_id' => ['nullable', 'string'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'naver_url' => ['nullable', 'string', 'max:500'],
            'is_overseas' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
            'theme_ids' => ['nullable', 'array', 'max:2'],
            'theme_ids.*' => ['integer', 'exists:themes,id'],
            'original_name' => ['nullable', 'string', 'max:255'],
        ]);

        $themeIds = $data['theme_ids'] ?? [];
        unset($data['theme_ids']);

        // naver_url → naver_place_id 파싱 (빈 문자열이면 기존 값 유지, 유효값이면 덮어쓰기)
        $naverUrlInput = $data['naver_url'] ?? null;
        unset($data['naver_url']);
        if ($naverUrlInput !== null && $naverUrlInput !== '') {
            $parsed = app(NaverUrlParser::class)->extractPlaceId($naverUrlInput);
            if ($parsed) {
                $data['naver_place_id'] = $parsed;
                $data['naver_matched_at'] = now();
            }
        }

        if (!empty($data['opening_hours'])) {
            $data['opening_hours'] = json_decode($data['opening_hours'], true);
        } else {
            $data['opening_hours'] = null;
        }

        $data['is_overseas'] = (bool) ($data['is_overseas'] ?? false);
        // personal-only-v1: 공개 장소 노출 차단 — 항상 비공개로 강제 (기존 true 였던 장소도 update 시 false로 정정)
        $data['is_public'] = false;

        if (empty($data['lat']) || empty($data['lng'])) {
            $addr = $data['road_address'] ?? $data['address'] ?? '';
            if ($addr !== '') {
                [$lat, $lng] = $this->forwardGeocode($addr, $data['is_overseas']);
                $data['lat'] = $lat;
                $data['lng'] = $lng;
            }
        }

        $images = $request->file('images', []);
        unset($data['images']);

        $oldLat = $place->lat;
        $oldLng = $place->lng;

        $place->update($data);
        $place->themes()->sync($themeIds);

        // 새 이미지 추가 (기존 이미지 수 + 신규 <= 5)
        $existingCount = $place->images()->count();
        $processor = app(ImageProcessor::class);
        foreach ($images as $i => $file) {
            if ($existingCount + $i + 1 > 5) break;
            try {
                $path = $processor->processPlaceImage($file, 'places/' . $place->id);
            } catch (\Throwable $e) {
                Log::warning('image process failed: ' . $e->getMessage(), ['place_id' => $place->id]);
                continue;
            }
            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'sort_order' => $existingCount + $i,
            ]);
        }

        $placeId = $place->id;
        $needMapRegen = ($place->lat !== null && $place->lng !== null) &&
            ($oldLat != $place->lat || $oldLng != $place->lng || empty($place->thumbnail));
        defer(function () use ($placeId, $needMapRegen) {
            $p = Place::find($placeId);
            if (!$p) return;
            if ($needMapRegen) $this->generateMapThumbnail($p);
            $this->tryMatchNaverPlaceId($p);
        });

        return redirect()->route('places.show', $place)->with('success', '수정되었어요.');
    }

    /**
     * 저장/수정 직후 네이버 플레이스 ID 동기 매칭.
     * - 수동 입력값(naver_place_id)이 이미 있으면 스킵 (수동이 우선)
     * - 해외 장소 스킵
     * - 국내 좌표(lat 33~39, lng 124~132)만 시도 (Matcher 내부에서도 재검증)
     * - 매칭 결과 (성공/실패 무관) naver_matched_at 에 기록 → 중복 호출 방지
     */
    private function tryMatchNaverPlaceId(Place $place): void
    {
        // 수동 입력된 place_id가 있으면 자동 매칭 스킵
        if (!empty($place->naver_place_id)) return;
        if ($place->is_overseas) return;

        $lat = (float) $place->lat;
        $lng = (float) $place->lng;
        $inKorea = ($lat >= 33.0 && $lat <= 39.0 && $lng >= 124.0 && $lng <= 132.0);
        if (!$inKorea) return;

        try {
            $matcher = app(NaverPlaceMatcher::class);
            $placeId = $matcher->match(
                $place->name,
                $lat,
                $lng,
                $place->road_address ?: $place->address
            );
            $place->forceFill([
                'naver_place_id' => $placeId ?: $place->naver_place_id,
                'naver_matched_at' => now(),
            ])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('naver match hook failed: ' . $e->getMessage(), ['place_id' => $place->id]);
        }
    }

    public function reorder(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        $siblings = Place::where('user_id', $place->user_id)
            ->where('category_id', $place->category_id)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $idx = $siblings->search(fn($p) => $p->id === $place->id);
        if ($idx === false) return response()->json(['ok' => false]);

        $swapIdx = $data['direction'] === 'up' ? $idx - 1 : $idx + 1;
        if ($swapIdx < 0 || $swapIdx >= $siblings->count()) {
            return response()->json(['ok' => false, 'error' => 'already_at_edge']);
        }

        $other = $siblings[$swapIdx];
        $tmpOrder = $place->sort_order;
        $place->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $tmpOrder]);

        return response()->json(['ok' => true]);
    }

    public function bulkReorder(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);
        $userId = $request->user()->id;
        $ids = array_values(array_unique($data['order']));
        $valid = Place::where('user_id', $userId)->whereIn('id', $ids)->pluck('id')->all();
        foreach ($ids as $i => $id) {
            if (!in_array($id, $valid)) continue;
            Place::where('id', $id)->where('user_id', $userId)->update(['sort_order' => $i]);
        }
        return response()->json(['ok' => true]);
    }

    public function quickAddImages(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        $existingCount = $place->images()->count();
        $processor = app(ImageProcessor::class);
        $added = 0;

        foreach ($request->file('images', []) as $i => $file) {
            if ($existingCount + $i + 1 > 5) break;
            try {
                $path = $processor->processPlaceImage($file, 'places/' . $place->id);
            } catch (\Throwable $e) {
                Log::warning('quick image failed: ' . $e->getMessage(), ['place_id' => $place->id]);
                continue;
            }
            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'sort_order' => $existingCount + $i,
            ]);
            $added++;
        }

        return redirect()->route('places.show', $place)->with('success', "이미지 {$added}장이 추가되었어요.");
    }

    public function destroyImage(PlaceImage $placeImage, Request $request)
    {
        $place = $placeImage->place;
        abort_unless($place->user_id === $request->user()?->id, 403);

        Storage::disk('public')->delete([$placeImage->path, $placeImage->thumb_path]);
        $placeImage->delete();

        return response()->json(['ok' => true]);
    }

    public function reorderImages(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        foreach ($data['ids'] as $i => $id) {
            PlaceImage::where('id', $id)->where('place_id', $place->id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);
        foreach ($place->images as $img) {
            Storage::disk('public')->delete([$img->path, $img->thumb_path]);
        }
        if ($place->thumbnail) {
            Storage::disk('public')->delete($place->thumbnail);
        }
        $place->delete();
        return redirect('/')->with('success', '삭제되었어요.');
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $places = Place::where('user_id', $user->id)->whereIn('id', $data['ids'])->with('images')->get();

        foreach ($places as $place) {
            foreach ($place->images as $img) {
                Storage::disk('public')->delete([$img->path, $img->thumb_path]);
            }
            if ($place->thumbnail) {
                Storage::disk('public')->delete($place->thumbnail);
            }
            $place->delete();
        }

        return response()->json(['ok' => true, 'deleted' => $places->count()]);
    }

    public function bulkMove(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $user = $request->user();
        $category = Category::where('id', $data['category_id'])->where('user_id', $user->id)->firstOrFail();

        $count = Place::where('user_id', $user->id)
            ->whereIn('id', $data['ids'])
            ->update(['category_id' => $category->id]);

        return response()->json(['ok' => true, 'moved' => $count, 'category_name' => $category->name]);
    }

    public function toggleStatus(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
        ]);

        $place->update([
            'status' => $data['status'],
            'visited_at' => $data['status'] === 'visited' ? ($data['visited_at'] ?? now()->toDateString()) : null,
        ]);

        return response()->json([
            'ok' => true,
            'status' => $place->status,
            'visited_at' => $place->visited_at?->format('Y.m.d'),
        ]);
    }

    // 테마별 내 장소 (로그인 사용자)
    public function placesByTheme(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['items' => []]);
        }
        $slug = trim((string) $request->input('theme', ''));
        $query = Place::where('user_id', $request->user()->id)
            ->where('is_visible', true)
            ->select(['id', 'name', 'category_id', 'road_address', 'address', 'lat', 'lng', 'user_id'])
            ->with(['category:id,name', 'themes:id,name']);

        if ($slug !== '') {
            $query->whereHas('themes', fn($q) => $q->where('slug', $slug));
        }

        $items = $query->latest()->limit(100)->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category?->name,
            'address' => $p->road_address ?: $p->address,
            'themes' => $p->themes->pluck('name'),
            'lat' => $p->lat,
            'lng' => $p->lng,
        ]);

        return response()->json(['items' => $items]);
    }

    // 테마별 공개 장소 (추후 에디터스픽 등)
    public function placesPublicByTheme(Request $request)
    {
        $slug = trim((string) $request->input('theme', ''));
        $query = Place::where('is_public', 1)
            ->where('is_visible', true)
            ->with(['category', 'themes', 'user:id,name']);

        if ($slug !== '') {
            $query->whereHas('themes', fn($q) => $q->where('slug', $slug));
        }

        $items = $query->latest()->limit(50)->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category?->name,
            'address' => $p->road_address ?: $p->address,
            'themes' => $p->themes->pluck('name'),
            'user' => $p->user?->name,
            'lat' => $p->lat,
            'lng' => $p->lng,
        ]);

        return response()->json(['items' => $items]);
    }

    // 카카오 로컬 API 프록시 (검색)
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['documents' => []]);

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $hasGeo = $lat && $lng && is_numeric($lat) && is_numeric($lng);

        $headers = ['Authorization' => 'KakaoAK ' . $key];
        $seen = [];
        $allDocs = [];

        // 1차: 거리순 (위치 있을 때) 또는 정확도순
        $params1 = ['query' => $q, 'size' => 15];
        if ($hasGeo) {
            $params1['y'] = $lat;
            $params1['x'] = $lng;
            $params1['sort'] = 'distance';
        }
        $res1 = Http::withHeaders($headers)
            ->get('https://dapi.kakao.com/v2/local/search/keyword.json', $params1);
        foreach (($res1->json()['documents'] ?? []) as $d) {
            if (!isset($seen[$d['id']])) {
                $seen[$d['id']] = true;
                $allDocs[] = $d;
            }
        }

        // 2차: 정확도순 (랜드마크/역 등 거리순에서 누락되는 결과 보완)
        if ($hasGeo) {
            $params2 = ['query' => $q, 'size' => 15];
            $res2 = Http::withHeaders($headers)
                ->get('https://dapi.kakao.com/v2/local/search/keyword.json', $params2);
            foreach (($res2->json()['documents'] ?? []) as $d) {
                if (!isset($seen[$d['id']])) {
                    $seen[$d['id']] = true;
                    $allDocs[] = $d;
                }
            }
        }

        // 거리 기반 점수제 정렬 (정확일치 보너스 적용)
        $nq = str_replace(' ', '', $q);

        $EXACT_BONUS = 3000;      // 정확일치: 3km 보너스
        $STARTS_WITH_BONUS = 1500; // 시작일치: 1.5km 보너스
        $CONTAINS_BONUS = 500;     // 포함일치: 0.5km 보너스

        $matched = [];
        $unmatched = [];
        foreach ($allDocs as $d) {
            $np = str_replace(' ', '', $d['place_name'] ?? '');
            $nameContainsQuery = ($np === $nq) || str_starts_with($np, $nq) || str_contains($np, $nq);
            if ($nameContainsQuery) {
                $matched[] = $d;
            } else {
                $unmatched[] = $d;
            }
        }

        if ($hasGeo) {
            $scoreSort = function (array $doc) use ($lat, $lng, $nq, $EXACT_BONUS, $STARTS_WITH_BONUS, $CONTAINS_BONUS): float {
                $distM = self::haversineDist((float) $lat, (float) $lng, (float) $doc['y'], (float) $doc['x']) * 1000;
                $np = str_replace(' ', '', $doc['place_name'] ?? '');
                $bonus = 0;
                if ($np === $nq) {
                    $bonus = $EXACT_BONUS;
                } elseif (str_starts_with($np, $nq)) {
                    $bonus = $STARTS_WITH_BONUS;
                } elseif (str_contains($np, $nq)) {
                    $bonus = $CONTAINS_BONUS;
                }
                return $distM - $bonus;
            };
            usort($matched, fn($a, $b) => $scoreSort($a) <=> $scoreSort($b));
            usort($unmatched, fn($a, $b) =>
                self::haversineDist((float) $lat, (float) $lng, (float) $a['y'], (float) $a['x'])
                <=> self::haversineDist((float) $lat, (float) $lng, (float) $b['y'], (float) $b['x'])
            );
        }

        $docs = count($matched) > 0 ? $matched : $unmatched;

        return response()->json([
            'documents' => array_values($docs),
            'meta' => $res1->json()['meta'] ?? [],
        ]);
    }

    public function buildingName(Request $request)
    {
        $roadAddr = trim((string) $request->input('road_address', ''));
        if ($roadAddr === '') return response()->json(['building_name' => '']);

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['building_name' => '']);

        try {
            $res = Http::withHeaders(['Authorization' => 'KakaoAK ' . $key])
                ->timeout(3)
                ->get('https://dapi.kakao.com/v2/local/search/address.json', [
                    'query' => $roadAddr,
                    'analyze_type' => 'exact',
                ]);
            $docs = $res->json()['documents'] ?? [];
            foreach ($docs as $d) {
                $bn = $d['road_address']['building_name'] ?? '';
                if ($bn !== '') {
                    return response()->json(['building_name' => $bn]);
                }
            }
        } catch (\Throwable $e) {
            Log::info('building_name lookup failed', ['msg' => $e->getMessage()]);
        }
        return response()->json(['building_name' => '']);
    }

    private static function haversineDist(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function searchNearby(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        if (!$lat || !$lng || !is_numeric($lat) || !is_numeric($lng)) {
            return response()->json(['documents' => []]);
        }

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['documents' => []]);

        $categories = ['FD6', 'CE7', 'AT4', 'CT1', 'AD5', 'HP8'];
        $all = [];
        $seen = [];

        foreach ($categories as $cat) {
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'KakaoAK ' . $key,
            ])->get('https://dapi.kakao.com/v2/local/search/category.json', [
                'category_group_code' => $cat,
                'x' => $lng,
                'y' => $lat,
                'radius' => 500,
                'sort' => 'distance',
                'size' => 15,
            ]);

            foreach (($res->json()['documents'] ?? []) as $doc) {
                $id = $doc['id'] ?? '';
                if ($id && isset($seen[$id])) continue;
                $seen[$id] = true;
                $all[] = $doc;
            }
        }

        usort($all, fn($a, $b) => ($a['distance'] ?? 9999) <=> ($b['distance'] ?? 9999));

        return response()->json(['documents' => array_slice($all, 0, 15)]);
    }

    public function searchNearbyOverseas(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        if (!$lat || !$lng || !is_numeric($lat) || !is_numeric($lng)) {
            return response()->json(['documents' => []]);
        }

        $key = config('services.google_places.api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $body = [
            'includedTypes' => ['restaurant', 'cafe', 'tourist_attraction', 'lodging', 'shopping_mall', 'museum', 'park', 'bar', 'bakery', 'spa'],
            'locationRestriction' => [
                'circle' => [
                    'center' => ['latitude' => (float) $lat, 'longitude' => (float) $lng],
                    'radius' => 500.0,
                ],
            ],
            'maxResultCount' => 15,
            'languageCode' => 'ko',
        ];

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.internationalPhoneNumber,places.location,places.primaryType,places.regularOpeningHours',
        ])->post('https://places.googleapis.com/v1/places:searchNearby', $body);

        $data = $res->json();
        $places = $data['places'] ?? [];

        $origin = ['lat' => (float) $lat, 'lng' => (float) $lng];
        $documents = array_map(function ($p) use ($origin) {
            $pLat = (float) ($p['location']['latitude'] ?? 0);
            $pLng = (float) ($p['location']['longitude'] ?? 0);
            $distance = $this->haversineDistance($origin['lat'], $origin['lng'], $pLat, $pLng);

            return [
                'id' => $p['id'] ?? '',
                'place_name' => $p['displayName']['text'] ?? '',
                'road_address_name' => $p['formattedAddress'] ?? '',
                'address_name' => $p['formattedAddress'] ?? '',
                'phone' => $p['internationalPhoneNumber'] ?? '',
                'opening_hours' => $p['regularOpeningHours']['weekdayDescriptions'] ?? null,
                'x' => (string) ($p['location']['longitude'] ?? ''),
                'y' => (string) ($p['location']['latitude'] ?? ''),
                'category_group_name' => $p['primaryType'] ?? '',
                'distance' => $distance,
            ];
        }, $places);

        usort($documents, fn($a, $b) => ($a['distance'] ?? 9999) <=> ($b['distance'] ?? 9999));

        return response()->json(['documents' => $documents]);
    }

    private function haversineDistance($lat1, $lng1, $lat2, $lng2)
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    // Google Places API (New) Text Search 프록시
    public function searchOverseas(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['documents' => []]);

        $key = config('services.google_places.api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $body = [
            'textQuery' => $q,
            'languageCode' => 'ko',
            'maxResultCount' => 15,
        ];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        if ($lat && $lng && is_numeric($lat) && is_numeric($lng)) {
            $body['locationBias'] = [
                'circle' => [
                    'center' => ['latitude' => (float) $lat, 'longitude' => (float) $lng],
                    'radius' => 50000.0,
                ],
            ];
        }

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.internationalPhoneNumber,places.location,places.primaryType,places.regularOpeningHours',
        ])->post('https://places.googleapis.com/v1/places:searchText', $body);

        $data = $res->json();
        $places = $data['places'] ?? [];

        $places = array_filter($places, function ($p) {
            $lat = $p['location']['latitude'] ?? 0;
            $lng = $p['location']['longitude'] ?? 0;
            $isKorea = $lat >= 33.0 && $lat <= 38.7 && $lng >= 124.5 && $lng <= 132.0;
            return !$isKorea;
        });

        $documents = array_values(array_map(function ($p) {
            return [
                'id' => $p['id'] ?? '',
                'place_name' => $p['displayName']['text'] ?? '',
                'road_address_name' => $p['formattedAddress'] ?? '',
                'address_name' => $p['formattedAddress'] ?? '',
                'phone' => $p['internationalPhoneNumber'] ?? '',
                'opening_hours' => $p['regularOpeningHours']['weekdayDescriptions'] ?? null,
                'x' => (string) ($p['location']['longitude'] ?? ''),
                'y' => (string) ($p['location']['latitude'] ?? ''),
                'category_group_name' => $p['primaryType'] ?? '',
            ];
        }, $places));

        return response()->json(['documents' => $documents]);
    }

    // Google Places Autocomplete (New) 프록시
    public function autocompleteOverseas(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['suggestions' => []]);

        $key = config('services.google_places.api_key');
        if (!$key) return response()->json(['suggestions' => [], 'error' => 'no_key']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
        ])->post('https://places.googleapis.com/v1/places:autocomplete', [
            'input' => $q,
            'languageCode' => 'ko',
        ]);

        $data = $res->json();
        $raw = $data['suggestions'] ?? [];

        $suggestions = [];
        foreach ($raw as $s) {
            $p = $s['placePrediction'] ?? null;
            if (!$p) continue;
            $suggestions[] = [
                'place_id' => $p['placeId'] ?? '',
                'name' => $p['structuredFormat']['mainText']['text'] ?? ($p['text']['text'] ?? ''),
                'description' => $p['structuredFormat']['secondaryText']['text'] ?? '',
                'types' => $p['types'] ?? [],
            ];
        }

        return response()->json(['suggestions' => $suggestions]);
    }

    // Google Place Details (place_id → 좌표/주소/전화번호)
    public function placeDetail(Request $request)
    {
        $placeId = trim((string) $request->input('place_id', ''));
        if ($placeId === '') return response()->json(['error' => 'no_place_id']);

        $key = config('services.google_places.api_key');
        if (!$key) return response()->json(['error' => 'no_key']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'id,displayName,formattedAddress,internationalPhoneNumber,location,primaryType,regularOpeningHours',
        ])->get("https://places.googleapis.com/v1/places/{$placeId}", [
            'languageCode' => 'ko',
        ]);

        $p = $res->json();

        $openingHours = null;
        if (!empty($p['regularOpeningHours']['weekdayDescriptions'])) {
            $openingHours = $p['regularOpeningHours']['weekdayDescriptions'];
        }

        return response()->json([
            'id' => $p['id'] ?? $placeId,
            'place_name' => $p['displayName']['text'] ?? '',
            'road_address_name' => $p['formattedAddress'] ?? '',
            'address_name' => $p['formattedAddress'] ?? '',
            'phone' => $p['internationalPhoneNumber'] ?? '',
            'opening_hours' => $openingHours,
            'x' => (string) ($p['location']['longitude'] ?? ''),
            'y' => (string) ($p['location']['latitude'] ?? ''),
            'category_group_name' => $p['primaryType'] ?? '',
        ]);
    }

    // 주소 → 좌표 (forward geocoding) 프록시
    public function forwardGeocodeApi(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $isOverseas = filter_var($request->input('overseas'), FILTER_VALIDATE_BOOLEAN);
        if ($q === '') return response()->json(['lat' => null, 'lng' => null]);

        [$lat, $lng] = $this->forwardGeocode($q, $isOverseas);
        return response()->json(['lat' => $lat, 'lng' => $lng]);
    }

    // 내부용: 주소 문자열을 좌표 [lat, lng]로 변환. 실패 시 [null, null].
    private function forwardGeocode(string $address, bool $isOverseas): array
    {
        if ($isOverseas) {
            $key = config('services.google_maps.api_key');
            if (!$key) return [null, null];
            $res = \Illuminate\Support\Facades\Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $key,
                'language' => 'ko',
            ]);
            $data = $res->json();
            $loc = $data['results'][0]['geometry']['location'] ?? null;
            if (!$loc) return [null, null];
            return [(float) $loc['lat'], (float) $loc['lng']];
        }

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return [null, null];
        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'KakaoAK ' . $key,
        ])->get('https://dapi.kakao.com/v2/local/search/address.json', [
            'query' => $address,
        ]);
        $data = $res->json();
        $doc = $data['documents'][0] ?? null;
        if (!$doc) return [null, null];
        return [(float) $doc['y'], (float) $doc['x']];
    }

    /**
     * 정적 지도 프록시 — 국내=Naver, 해외=Google
     * GET /api/static-map?lat=&lng=&overseas=0|1&w=&h=
     * 결과는 storage/app/public/static-maps/{hash}.jpg 에 캐시
     */
    public function staticMap(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'overseas' => ['nullable'],
            'w' => ['nullable', 'integer', 'min:80', 'max:800'],
            'h' => ['nullable', 'integer', 'min:80', 'max:800'],
        ]);

        $lat = round((float) $request->input('lat'), 5);
        $lng = round((float) $request->input('lng'), 5);
        $overseas = (bool) $request->input('overseas');
        $w = (int) ($request->input('w') ?? 400);
        $h = (int) ($request->input('h') ?? 300);

        $hash = sha1("{$lat}|{$lng}|" . ($overseas ? 'g' : 'n') . "|{$w}x{$h}");
        $webpPath = storage_path("app/public/static-maps/{$hash}.webp");
        $jpgPath = storage_path("app/public/static-maps/{$hash}.jpg");

        if (is_file($webpPath)) {
            return response()->file($webpPath, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=2592000',
            ]);
        }

        if (!is_file($jpgPath)) {
            if (!is_dir(dirname($jpgPath))) {
                @mkdir(dirname($jpgPath), 0775, true);
            }
            try {
                $body = $overseas
                    ? $this->fetchGoogleStaticMap($lat, $lng, $w, $h)
                    : $this->fetchNaverStaticMap($lat, $lng, $w, $h);
                if ($body) {
                    $processor = app(ImageProcessor::class);
                    if ($processor->saveAsWebp($body, "static-maps/{$hash}.webp", max($w, $h), 72)) {
                        return response()->file($webpPath, [
                            'Content-Type' => 'image/webp',
                            'Cache-Control' => 'public, max-age=2592000',
                        ]);
                    }
                    file_put_contents($jpgPath, $body);
                }
            } catch (\Throwable $e) {
                Log::warning('[static-map] fetch fail: ' . $e->getMessage());
            }
        }

        if (is_file($jpgPath)) {
            return response()->file($jpgPath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=2592000',
            ]);
        }
        return response('', 404);
    }

    private function fetchNaverStaticMap(float $lat, float $lng, int $w, int $h): ?string
    {
        $cid = config('services.naver_map.client_id');
        $sec = config('services.naver_map.client_secret');
        if (!$cid || !$sec) return null;

        $resp = Http::withHeaders([
            'X-NCP-APIGW-API-KEY-ID' => $cid,
            'X-NCP-APIGW-API-KEY' => $sec,
        ])->timeout(6)->get('https://maps.apigw.ntruss.com/map-static/v2/raster', [
            'w' => $w,
            'h' => $h,
            'level' => 16,
            'center' => "{$lng},{$lat}",
            'markers' => "type:n|size:mid|pos:{$lng} {$lat}",
            'lang' => 'ko',
            'format' => 'jpeg',
        ]);
        if (!$resp->successful()) {
            Log::warning('[static-map] naver ' . $resp->status() . ' ' . substr($resp->body(), 0, 200));
            return null;
        }
        return $resp->body();
    }

    private function fetchGoogleStaticMap(float $lat, float $lng, int $w, int $h): ?string
    {
        $key = config('services.google_maps.api_key');
        if (!$key) return null;

        $resp = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/staticmap', [
            'center' => "{$lat},{$lng}",
            'zoom' => 14,
            'size' => "{$w}x{$h}",
            'markers' => "color:red|{$lat},{$lng}",
            'language' => 'ko',
            'key' => $key,
        ]);
        if (!$resp->successful()) {
            Log::warning('[static-map] google ' . $resp->status() . ' ' . substr($resp->body(), 0, 200));
            return null;
        }
        return $resp->body();
    }

    // 역지오코딩 통합 프록시 (?provider=google|naver)
    public function reverseGeocode(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $provider = $request->input('provider', 'naver');
        if (!$lat || !$lng) return response()->json(['address' => '']);

        if ($provider === 'google') {
            return $this->reverseGeocodeGoogle($lat, $lng);
        }
        return $this->reverseGeocodeKakao($lat, $lng);
    }

    private function reverseGeocodeKakao($lat, $lng)
    {
        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['address' => '']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'KakaoAK ' . $key,
        ])->get('https://dapi.kakao.com/v2/local/geo/coord2address.json', [
            'x' => $lng,
            'y' => $lat,
        ]);

        $data = $res->json();
        $doc = $data['documents'][0] ?? null;
        if (!$doc) return response()->json(['address' => '']);

        $road = $doc['road_address']['address_name'] ?? '';
        $jibun = $doc['address']['address_name'] ?? '';
        $address = $road ?: $jibun;
        return response()->json([
            'address' => $address,
            'region' => \App\Http\Controllers\TrendingController::regionOfStatic($address, false),
        ]);
    }

    private function reverseGeocodeGoogle($lat, $lng)
    {
        $key = config('services.google_maps.api_key');
        if (!$key) return response()->json(['address' => '']);

        $res = \Illuminate\Support\Facades\Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "$lat,$lng",
            'key' => $key,
            'language' => 'ko',
        ]);

        $data = $res->json();
        $results = $data['results'] ?? [];
        $address = !empty($results[0]['formatted_address']) ? $results[0]['formatted_address'] : '';

        return response()->json([
            'address' => $address,
            'region' => \App\Http\Controllers\TrendingController::regionOfStatic($address, true),
        ]);
    }

    private function reverseGeocodeNaver($lat, $lng)
    {
        $clientId = config('services.naver_map.client_id');
        $clientSecret = config('services.naver_map.client_secret');
        if (!$clientId || !$clientSecret) return response()->json(['address' => '']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'X-NCP-APIGW-API-KEY-ID' => $clientId,
            'X-NCP-APIGW-API-KEY' => $clientSecret,
        ])->get('https://naveropenapi.apigw.ntruss.com/map-reversegeocode/v2/gc', [
            'coords' => "$lng,$lat",
            'output' => 'json',
            'orders' => 'roadaddr,addr',
        ]);

        $data = $res->json();
        $results = $data['results'] ?? [];
        $address = '';

        foreach ($results as $r) {
            if ($r['name'] === 'roadaddr') {
                $land = $r['land'] ?? [];
                $region = $r['region'] ?? [];
                $parts = [];
                foreach (['area1','area2','area3'] as $k) {
                    if (!empty($region[$k]['name'])) $parts[] = $region[$k]['name'];
                }
                if (!empty($land['name'])) $parts[] = $land['name'];
                if (!empty($land['number1'])) $parts[] = $land['number1'];
                $address = implode(' ', $parts);
                break;
            }
            if ($r['name'] === 'addr' && !$address) {
                $region = $r['region'] ?? [];
                $land = $r['land'] ?? [];
                $parts = [];
                foreach (['area1','area2','area3','area4'] as $k) {
                    if (!empty($region[$k]['name'])) $parts[] = $region[$k]['name'];
                }
                if (!empty($land['number1'])) {
                    $num = $land['number1'];
                    if (!empty($land['number2'])) $num .= '-' . $land['number2'];
                    $parts[] = $num;
                }
                $address = implode(' ', $parts);
            }
        }

        return response()->json(['address' => $address]);
    }

    // 전화번호/영업시간 폴백: 카카오에서 못 찾은 국내 장소를 구글 Places로 조회
    // (네이버 지역검색은 telephone 필드가 deprecated되어 항상 빈 값이라 사용 불가)
    public function phoneFallback(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $address = trim((string) $request->input('address', ''));
        if ($name === '') return response()->json(['phone' => '', 'opening_hours' => null, 'detail_address' => '', 'source' => null]);

        $info = $this->infoFromGooglePlaces($name, $address);
        if ($info['phone'] || $info['opening_hours'] || $info['detail_address']) {
            return response()->json([
                'phone' => $info['phone'],
                'opening_hours' => $info['opening_hours'],
                'detail_address' => $info['detail_address'],
                'source' => 'google',
            ]);
        }

        return response()->json(['phone' => '', 'opening_hours' => null, 'detail_address' => '', 'source' => null]);
    }

    private function infoFromGooglePlaces(string $name, string $address): array
    {
        $empty = ['phone' => '', 'opening_hours' => null, 'detail_address' => ''];
        $key = config('services.google_places.api_key');
        if (!$key) return $empty;
        $query = trim($address !== '' ? ($address . ' ' . $name) : $name);
        try {
            $res = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'places.displayName,places.internationalPhoneNumber,places.nationalPhoneNumber,places.formattedAddress,places.regularOpeningHours',
            ])->timeout(6)->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $query,
                'languageCode' => 'ko',
                'regionCode' => 'KR',
                'maxResultCount' => 3,
            ]);
            if (!$res->successful()) return $empty;
            $places = $res->json()['places'] ?? [];
            $normName = $this->normalizeName($name);
            foreach ($places as $p) {
                $title = $this->normalizeName($p['displayName']['text'] ?? '');
                $tel = trim($p['nationalPhoneNumber'] ?? ($p['internationalPhoneNumber'] ?? ''));
                $hours = $p['regularOpeningHours']['weekdayDescriptions'] ?? null;
                $detailAddr = $this->extractDetailAddress($p['formattedAddress'] ?? '', $address);
                if (!$tel && !$hours && !$detailAddr) continue;
                if ($title && $normName && (str_contains($title, $normName) || str_contains($normName, $title))) {
                    return ['phone' => $tel, 'opening_hours' => $hours, 'detail_address' => $detailAddr];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Places lookup error', ['msg' => $e->getMessage()]);
        }
        return $empty;
    }

    private function extractDetailAddress(string $googleAddr, string $kakaoRoad): string
    {
        if ($googleAddr === '' || $kakaoRoad === '') return '';
        $kakaoTokens = preg_split('/\s+/', trim($kakaoRoad));
        $lastToken = end($kakaoTokens);
        $pos = mb_strrpos($googleAddr, $lastToken);
        if ($pos === false) return '';
        $after = mb_substr($googleAddr, $pos + mb_strlen($lastToken));
        return trim($after) ?: '';
    }

    private function normalizeName(string $s): string
    {
        $s = preg_replace('/\s+/u', '', $s);
        $s = preg_replace('/[\p{P}\p{S}]/u', '', $s);
        return mb_strtolower((string) $s);
    }

    // 장소 좌표로 Static Map 썸네일을 생성/저장하고 places.thumbnail 업데이트
    private function generateMapThumbnail(Place $place): void
    {
        if ($place->lat === null || $place->lng === null) return;

        $lat = (float) $place->lat;
        $lng = (float) $place->lng;
        $w = 600;
        $h = 400;
        $zoom = 16;

        try {
            $binary = null;

            if ($place->is_overseas) {
                $key = config('services.google_maps.api_key');
                if (!$key) return;

                $params = [
                    'center' => "$lat,$lng",
                    'zoom' => $zoom,
                    'size' => "{$w}x{$h}",
                    'scale' => 2,
                    'maptype' => 'roadmap',
                    'markers' => "color:red|$lat,$lng",
                    'language' => 'ko',
                    'key' => $key,
                ];
                $res = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/staticmap', $params);
                if (!$res->successful()) {
                    Log::warning('Google Static Map failed', ['status' => $res->status(), 'place_id' => $place->id]);
                    return;
                }
                $binary = $res->body();
            } else {
                $clientId = config('services.naver_map.client_id');
                $clientSecret = config('services.naver_map.client_secret');
                if (!$clientId || !$clientSecret) return;

                $params = [
                    'w' => $w,
                    'h' => $h,
                    'center' => "$lng,$lat",
                    'level' => $zoom,
                    'scale' => 2,
                    'format' => 'jpg',
                    'markers' => "type:d|size:mid|pos:$lng $lat",
                ];
                $res = Http::withHeaders([
                    'X-NCP-APIGW-API-KEY-ID' => $clientId,
                    'X-NCP-APIGW-API-KEY' => $clientSecret,
                ])->timeout(10)->get('https://maps.apigw.ntruss.com/map-static/v2/raster', $params);
                if (!$res->successful()) {
                    Log::warning('Naver Static Map failed', ['status' => $res->status(), 'body' => $res->body(), 'place_id' => $place->id]);
                    return;
                }
                $binary = $res->body();
            }

            if (!$binary) return;

            $processor = app(ImageProcessor::class);
            $path = 'static-maps/' . sha1($place->id . $lat . $lng) . '.webp';
            if ($processor->saveAsWebp($binary, $path, 600, 72)) {
                $oldPath = 'places/thumb_' . $place->id . '.jpg';
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            } else {
                $path = 'places/thumb_' . $place->id . '.jpg';
                Storage::disk('public')->put($path, $binary);
            }

            $place->thumbnail = $path;
            $place->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Map thumbnail generation error', ['msg' => $e->getMessage(), 'place_id' => $place->id]);
        }
    }
}
