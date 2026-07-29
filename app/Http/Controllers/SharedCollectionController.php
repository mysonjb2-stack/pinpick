<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use App\Models\SharedCollection;
use App\Models\SharedPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\PlaceImage;
use App\Services\GoogleReviewService;
use App\Services\ImageProcessor;
use Illuminate\Support\Str;

class SharedCollectionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'place_ids' => 'nullable|array|min:1|max:200',
            'place_ids.*' => 'integer',
            'title' => 'required|string|max:100',
            'name_display_mode' => 'required|in:original,custom',
        ]);

        $user = Auth::user();

        if ($request->place_ids) {
            $places = Place::where('user_id', $user->id)
                ->whereIn('id', $request->place_ids)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->with(['images', 'themes'])
                ->get();
            $category = $places->first()?->category;
        } else {
            $category = Category::where('id', $request->category_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $places = Place::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->with(['images', 'themes'])
                ->get();
        }

        if ($places->isEmpty()) {
            return response()->json(['error' => '공유할 장소가 없습니다.'], 422);
        }

        $token = Str::random(32);
        while (SharedCollection::where('token', $token)->exists()) {
            $token = Str::random(32);
        }

        $collection = SharedCollection::create([
            'user_id' => $user->id,
            'token' => $token,
            'title' => $request->title,
            'source_category_id' => $category?->id,
            'name_display_mode' => $request->name_display_mode,
        ]);

        $isCustom = $request->name_display_mode === 'custom';
        $storageDir = "shared/{$collection->id}";
        Storage::disk('public')->makeDirectory($storageDir);

        foreach ($places as $i => $place) {
            $thumbnailUrl = null;
            $sourcePath = $this->findThumbnailPath($place);
            if ($sourcePath && Storage::disk('public')->exists($sourcePath)) {
                $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
                $destPath = "{$storageDir}/" . Str::random(20) . ".{$ext}";
                Storage::disk('public')->copy($sourcePath, $destPath);
                if ($ext !== 'webp') {
                    $processor = app(ImageProcessor::class);
                    $webpPath = $processor->convertToWebp($destPath, 800, 75);
                    if ($webpPath) $destPath = $webpPath;
                }
                $thumbnailUrl = asset('storage/' . $destPath);

                if ($i === 0) {
                    app(ImageProcessor::class)->generateOgImage($destPath);
                }
            }

            SharedPlace::create([
                'shared_collection_id' => $collection->id,
                'display_name' => $isCustom ? $place->name : ($place->original_name ?: $place->name),
                'original_place_name' => $place->original_name ?: $place->name,
                'address' => $place->road_address ?: $place->address,
                'jibeon_address' => ($place->road_address && $place->address && $place->address !== $place->road_address) ? $place->address : null,
                'building_name' => $place->building_name,
                'detail_location' => $place->detail_location,
                'phone' => $place->phone,
                'opening_hours' => $place->opening_hours,
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'category_label' => $place->category?->name ?? $category?->name ?? '',
                'themes' => $place->themes->pluck('name')->values()->all(),
                'memo' => $isCustom ? $place->memo : null,
                'thumbnail_url' => $thumbnailUrl,
                'external_place_id' => $place->kakao_place_id ?: $place->naver_place_id,
                'naver_place_id' => $place->naver_place_id,
                'google_place_id' => $place->google_place_id,
                'is_overseas' => (bool) $place->is_overseas,
                'sort_order' => $i,
            ]);
        }

        $collection->load('places');
        $firstThumb = $collection->places->first()?->thumbnail_url;
        if (!$firstThumb) {
            \App\Services\OgImageResolver::mapOgForShared($collection);
        }

        $shareUrl = url("/s/{$token}");

        return response()->json([
            'success' => true,
            'token' => $token,
            'url' => $shareUrl,
            'place_count' => $places->count(),
            'title' => $collection->title,
            'thumbnail_url' => $firstThumb,
        ]);
    }

    public function show(string $token)
    {
        $collection = SharedCollection::where('token', $token)->first();

        if (!$collection || !$collection->is_active) {
            return response()->view('shared.expired', [], 410);
        }

        $viewKey = "shared_view:{$collection->id}:" . (request()->ip() ?? 'unknown');
        if (!\Illuminate\Support\Facades\Cache::has($viewKey)) {
            $collection->increment('view_count');
            \Illuminate\Support\Facades\Cache::put($viewKey, true, 300);
        }
        $collection->load(['places', 'user:id,name']);

        $userCategories = [];
        if (Auth::check()) {
            $userCategories = Category::where('user_id', Auth::id())
                ->orderBy('sort_order')
                ->get(['id', 'name', 'icon']);
        }

        $googlePlaceIds = $collection->places
            ->pluck('google_place_id')
            ->filter()
            ->toArray();
        $googleReviews = GoogleReviewService::getReviewDataBulk($googlePlaceIds);

        return view('shared.show', compact('collection', 'userCategories', 'googleReviews'));
    }

    public function myLinks()
    {
        $collections = SharedCollection::where('user_id', Auth::id())
            ->withCount('places')
            ->orderByDesc('created_at')
            ->get();

        return view('mypage.shared-links', compact('collections'));
    }

    public function deactivate(SharedCollection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            abort(403);
        }

        $collection->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }

    public function saveToMyPinpick(Request $request, string $token)
    {
        $collection = SharedCollection::where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'place_ids' => 'required|array|min:1',
            'place_ids.*' => 'integer|exists:shared_places,id',
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

        $sharedPlaces = SharedPlace::where('shared_collection_id', $collection->id)
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

        foreach ($sharedPlaces as $sp) {
            $isDup = ($sp->external_place_id && in_array($sp->external_place_id, $existingExtIds))
                || ($sp->naver_place_id && in_array($sp->naver_place_id, $existingExtIds));
            if ($isDup) {
                $skipped++;
                continue;
            }

            $isOverseas = true;
            if ($sp->address) {
                foreach ($koreaProvinces as $prov) {
                    if (str_starts_with($sp->address, $prov)) {
                        $isOverseas = false;
                        break;
                    }
                }
            }

            $newPlace = Place::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $sp->display_name,
                'original_name' => $sp->original_place_name,
                'address' => $sp->jibeon_address,
                'road_address' => $sp->address,
                'building_name' => $sp->building_name,
                'detail_location' => $sp->detail_location,
                'phone' => $sp->phone,
                'opening_hours' => $sp->opening_hours,
                'lat' => $sp->latitude,
                'lng' => $sp->longitude,
                'memo' => $sp->memo,
                'status' => 'planned',
                'is_overseas' => $isOverseas,
                'is_public' => false,
                'sort_order' => ++$maxSort,
                'kakao_place_id' => $sp->external_place_id,
                'naver_place_id' => $sp->naver_place_id,
                'google_place_id' => $sp->google_place_id,
            ]);

            if (!empty($sp->themes)) {
                $themeIds = \App\Models\Theme::whereIn('name', $sp->themes)->pluck('id');
                if ($themeIds->isNotEmpty()) {
                    $newPlace->themes()->sync($themeIds);
                }
            }

            if ($sp->thumbnail_url) {
                $this->copySharedImage($sp, $newPlace);
            }

            $saved++;
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'skipped' => $skipped,
        ]);
    }

    public function checkDuplicates(Request $request, string $token)
    {
        $collection = SharedCollection::where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'place_ids' => 'required|array|min:1',
            'place_ids.*' => 'integer|exists:shared_places,id',
        ]);

        $user = Auth::user();
        $sharedPlaces = SharedPlace::where('shared_collection_id', $collection->id)
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

        $duplicates = 0;
        foreach ($sharedPlaces as $sp) {
            $isDup = ($sp->external_place_id && in_array($sp->external_place_id, $existingExtIds))
                || ($sp->naver_place_id && in_array($sp->naver_place_id, $existingExtIds));
            if ($isDup) {
                $duplicates++;
            }
        }

        return response()->json([
            'total' => count($request->place_ids),
            'duplicates' => $duplicates,
            'all_duplicates' => $duplicates >= count($request->place_ids),
        ]);
    }

    private function findThumbnailPath(Place $place): ?string
    {
        $firstImage = $place->images->first();
        if ($firstImage) {
            $thumbPath = \App\Services\ImageProcessor::thumbPathFor($firstImage->path);
            if (Storage::disk('public')->exists($thumbPath)) {
                return $thumbPath;
            }
            return $firstImage->path;
        }

        if ($place->thumbnail) {
            return $place->thumbnail;
        }

        return null;
    }

    private function copySharedImage(SharedPlace $sp, Place $newPlace): void
    {
        $url = $sp->thumbnail_url;
        $parsed = parse_url($url, PHP_URL_PATH);
        $storagePath = str_replace('/storage/', '', $parsed);

        if (!Storage::disk('public')->exists($storagePath)) {
            return;
        }

        $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'webp';
        $destDir = "places/{$newPlace->id}";
        Storage::disk('public')->makeDirectory($destDir);
        $destPath = "{$destDir}/" . Str::random(40) . ".{$ext}";

        Storage::disk('public')->copy($storagePath, $destPath);

        PlaceImage::create([
            'place_id' => $newPlace->id,
            'path' => $destPath,
            'sort_order' => 0,
        ]);

        $thumbPath = ImageProcessor::thumbPathFor($destPath);
        $sourceThumb = ImageProcessor::thumbPathFor($storagePath);
        if (Storage::disk('public')->exists($sourceThumb)) {
            Storage::disk('public')->copy($sourceThumb, $thumbPath);
        } else {
            app(ImageProcessor::class)->generateThumbFrom($destPath);
        }

        $newPlace->update(['thumbnail' => $thumbPath]);
    }
}
