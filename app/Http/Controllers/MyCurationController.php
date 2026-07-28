<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Models\CurationReport;
use App\Models\Place;
use App\Services\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MyCurationController extends Controller
{
    public function index()
    {
        $curations = Curation::byUser(Auth::id())
            ->withCount('places')
            ->orderByDesc('updated_at')
            ->get();

        return view('my.curations.index', compact('curations'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'icon']);

        $preselectedCategoryId = $request->get('category_id');
        $suggestTitle = $request->get('suggest_title', '');

        $curationCategories = config('curation_categories');

        return view('my.curations.create', compact('categories', 'preselectedCategoryId', 'suggestTitle', 'curationCategories'));
    }

    public function loadPlaces(Request $request)
    {
        $categoryId = $request->get('category_id');
        $user = Auth::user();

        $query = Place::where('user_id', $user->id)
            ->with('images')
            ->orderBy('sort_order');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $places = $query->get()->map(function ($p) {
            $photos = $p->images->map(fn($img) => $img->thumbUrl ?: $img->url)->toArray();
            return [
                'id' => $p->id,
                'name' => $p->name,
                'original_name' => $p->original_name,
                'address' => $p->road_address ?: $p->address,
                'jibeon_address' => $p->address,
                'road_address' => $p->road_address,
                'lat' => $p->lat,
                'lng' => $p->lng,
                'phone' => $p->phone,
                'opening_hours' => $p->opening_hours,
                'building_name' => $p->building_name,
                'category_label' => $p->category ? $p->category->name : '',
                'kakao_place_id' => $p->kakao_place_id,
                'naver_place_id' => $p->naver_place_id,
                'google_place_id' => $p->google_place_id,
                'is_overseas' => (bool) $p->is_overseas,
                'photos' => $photos,
                'photo_paths' => $p->images->pluck('path')->toArray(),
                'has_photo' => $p->images->isNotEmpty(),
                'thumbnail' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null,
            ];
        });

        return response()->json($places);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'title' => 'required|string|max:40',
            'description' => 'nullable|string|max:200',
            'category' => 'required|string|in:food,cafe,travel,outing',
            'place_ids' => 'required|array|min:3',
            'place_ids.*' => 'integer',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string|max:100',
            'terms_agreed' => 'required|accepted',
        ]);

        $places = Place::where('user_id', $user->id)
            ->whereIn('id', $data['place_ids'])
            ->with('images')
            ->get();

        if ($places->count() < 3) {
            return response()->json(['error' => '장소를 3곳 이상 선택해주세요.'], 422);
        }

        $hasPhoto = $places->contains(fn($p) => $p->images->isNotEmpty());
        if (!$hasPhoto) {
            return response()->json(['error' => '사진이 있는 장소가 1곳 이상 필요합니다.'], 422);
        }

        $curation = Curation::create([
            'title' => $data['title'],
            'slug' => Curation::generateSlug($data['title']),
            'type' => 'list',
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'author_type' => 'user',
            'author_user_id' => $user->id,
        ]);

        $koreaProvinces = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];

        $sortOrder = 0;
        foreach ($data['place_ids'] as $placeId) {
            $place = $places->firstWhere('id', $placeId);
            if (!$place) continue;

            $comment = $data['comments'][$placeId] ?? null;

            $photoPaths = [];
            $thumbUrl = null;
            if ($place->images->isNotEmpty()) {
                $curationDir = 'curations/' . $curation->id;
                Storage::disk('public')->makeDirectory($curationDir);

                foreach ($place->images->take(5) as $img) {
                    if (Storage::disk('public')->exists($img->path)) {
                        $ext = pathinfo($img->path, PATHINFO_EXTENSION) ?: 'webp';
                        $newPath = $curationDir . '/' . \Illuminate\Support\Str::random(30) . '.' . $ext;
                        Storage::disk('public')->copy($img->path, $newPath);

                        $thumbSrc = ImageProcessor::thumbPathFor($img->path);
                        if (Storage::disk('public')->exists($thumbSrc)) {
                            Storage::disk('public')->copy($thumbSrc, ImageProcessor::thumbPathFor($newPath));
                        }

                        $photoPaths[] = $newPath;
                    }
                }
                if (!empty($photoPaths)) {
                    $thumbPath = ImageProcessor::thumbPathFor($photoPaths[0]);
                    $thumbUrl = Storage::disk('public')->exists($thumbPath)
                        ? asset('storage/' . $thumbPath)
                        : asset('storage/' . $photoPaths[0]);
                }
            }

            $roadAddr = $place->road_address ?: '';
            $isOverseas = (bool) $place->is_overseas;
            if (!$isOverseas && $roadAddr) {
                $isOverseas = true;
                foreach ($koreaProvinces as $prov) {
                    if (str_starts_with($roadAddr, $prov)) {
                        $isOverseas = false;
                        break;
                    }
                }
            }

            CurationPlace::create([
                'curation_id' => $curation->id,
                'place_name' => $place->original_name ?: $place->name,
                'address' => $roadAddr,
                'jibeon_address' => $place->address,
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'category_label' => $place->category ? $place->category->name : null,
                'thumbnail_url' => $thumbUrl,
                'photos' => !empty($photoPaths) ? $photoPaths : null,
                'external_place_id' => $place->kakao_place_id,
                'naver_place_id' => $place->naver_place_id,
                'google_place_id' => $place->google_place_id,
                'is_overseas' => $isOverseas,
                'sort_order' => $sortOrder++,
                'editor_note' => $comment,
                'phone' => $place->phone,
                'opening_hours' => $place->opening_hours,
                'building_name' => $place->building_name,
            ]);
        }

        $proc = app(ImageProcessor::class);
        $curation->load('places');
        $photos = $curation->places->flatMap(function ($p) {
            return collect($p->photos ?? [])->map(fn($path) => asset('storage/' . $path));
        })->filter()->take(4)->toArray();

        if (count($photos) >= 1) {
            $cover = $proc->createMosaic(
                array_map(fn($url) => $url, $photos),
                'curations/covers'
            );
            if ($cover) {
                $curation->update(['cover_image' => $cover]);
            }
        }

        $firstPhotos = $curation->places->first()?->photos;
        if ($firstPhotos && !empty($firstPhotos)) {
            $proc->generateOgImage($firstPhotos[0]);
        } else {
            \App\Services\OgImageResolver::mapOgForCuration($curation);
        }

        return response()->json([
            'success' => true,
            'id' => $curation->id,
            'message' => '리스트가 제출됐어요! 검토 후 탐색 탭에 공개돼요.',
        ]);
    }

    public function edit(Curation $curation)
    {
        $user = Auth::user();
        abort_unless($curation->author_user_id === $user->id, 403);
        abort_unless(in_array($curation->status, ['draft', 'rejected', 'approved']), 403);

        $categories = Category::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'icon']);

        $curationCategories = config('curation_categories');

        $curation->load('places');

        return view('my.curations.edit', compact('curation', 'categories', 'curationCategories'));
    }

    public function update(Request $request, Curation $curation)
    {
        $user = Auth::user();
        abort_unless($curation->author_user_id === $user->id, 403);
        abort_unless(in_array($curation->status, ['draft', 'rejected', 'approved']), 403);

        $data = $request->validate([
            'title' => 'required|string|max:40',
            'description' => 'nullable|string|max:200',
            'category' => 'required|string|in:food,cafe,travel,outing',
            'place_ids' => 'required|array|min:3',
            'place_ids.*' => 'integer',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string|max:100',
            'terms_agreed' => 'required|accepted',
        ]);

        $places = Place::where('user_id', $user->id)
            ->whereIn('id', $data['place_ids'])
            ->with('images')
            ->get();

        if ($places->count() < 3) {
            return response()->json(['error' => '장소를 3곳 이상 선택해주세요.'], 422);
        }

        $hasPhoto = $places->contains(fn($p) => $p->images->isNotEmpty());
        if (!$hasPhoto) {
            return response()->json(['error' => '사진이 있는 장소가 1곳 이상 필요합니다.'], 422);
        }

        if ($curation->status === 'approved') {
            $snapshot = $this->buildSnapshot($curation);
            $curation->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'status' => 'pending',
                'rejected_reason' => null,
                'approved_snapshot' => $snapshot,
            ]);
        } else {
            $curation->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'status' => 'pending',
                'rejected_reason' => null,
            ]);
        }

        foreach ($curation->places as $old) {
            if ($old->photos) {
                foreach ($old->photos as $path) {
                    if (!Storage::disk('public')->exists($path)) continue;
                    $referenced = CurationPlace::where('id', '!=', $old->id)
                        ->whereJsonContains('photos', $path)->exists();
                    if (!$referenced) {
                        Storage::disk('public')->delete($path);
                        Storage::disk('public')->delete(ImageProcessor::thumbPathFor($path));
                    }
                }
            }
        }
        $curation->places()->delete();

        $koreaProvinces = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];

        $sortOrder = 0;
        foreach ($data['place_ids'] as $placeId) {
            $place = $places->firstWhere('id', $placeId);
            if (!$place) continue;

            $comment = $data['comments'][$placeId] ?? null;

            $photoPaths = [];
            $thumbUrl = null;
            if ($place->images->isNotEmpty()) {
                $curationDir = 'curations/' . $curation->id;
                Storage::disk('public')->makeDirectory($curationDir);

                foreach ($place->images->take(5) as $img) {
                    if (Storage::disk('public')->exists($img->path)) {
                        $ext = pathinfo($img->path, PATHINFO_EXTENSION) ?: 'webp';
                        $newPath = $curationDir . '/' . \Illuminate\Support\Str::random(30) . '.' . $ext;
                        Storage::disk('public')->copy($img->path, $newPath);

                        $thumbSrc = ImageProcessor::thumbPathFor($img->path);
                        if (Storage::disk('public')->exists($thumbSrc)) {
                            Storage::disk('public')->copy($thumbSrc, ImageProcessor::thumbPathFor($newPath));
                        }

                        $photoPaths[] = $newPath;
                    }
                }
                if (!empty($photoPaths)) {
                    $thumbPath = ImageProcessor::thumbPathFor($photoPaths[0]);
                    $thumbUrl = Storage::disk('public')->exists($thumbPath)
                        ? asset('storage/' . $thumbPath)
                        : asset('storage/' . $photoPaths[0]);
                }
            }

            $roadAddr = $place->road_address ?: '';
            $isOverseas = (bool) $place->is_overseas;
            if (!$isOverseas && $roadAddr) {
                $isOverseas = true;
                foreach ($koreaProvinces as $prov) {
                    if (str_starts_with($roadAddr, $prov)) {
                        $isOverseas = false;
                        break;
                    }
                }
            }

            CurationPlace::create([
                'curation_id' => $curation->id,
                'place_name' => $place->original_name ?: $place->name,
                'address' => $roadAddr,
                'jibeon_address' => $place->address,
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'category_label' => $place->category ? $place->category->name : null,
                'thumbnail_url' => $thumbUrl,
                'photos' => !empty($photoPaths) ? $photoPaths : null,
                'external_place_id' => $place->kakao_place_id,
                'naver_place_id' => $place->naver_place_id,
                'google_place_id' => $place->google_place_id,
                'is_overseas' => $isOverseas,
                'sort_order' => $sortOrder++,
                'editor_note' => $comment,
                'phone' => $place->phone,
                'opening_hours' => $place->opening_hours,
                'building_name' => $place->building_name,
            ]);
        }

        $proc = app(ImageProcessor::class);
        $curation->load('places');
        $photos = $curation->places->flatMap(function ($p) {
            return collect($p->photos ?? [])->map(fn($path) => asset('storage/' . $path));
        })->filter()->take(4)->toArray();

        if (count($photos) >= 1) {
            $cover = $proc->createMosaic($photos, 'curations/covers');
            if ($cover) {
                $curation->update(['cover_image' => $cover]);
            }
        }

        $firstPhotos = $curation->places->first()?->photos;
        if ($firstPhotos && !empty($firstPhotos)) {
            $proc->generateOgImage($firstPhotos[0]);
        } else {
            \App\Services\OgImageResolver::mapOgForCuration($curation);
        }

        return response()->json([
            'success' => true,
            'message' => '리스트가 수정·재제출됐어요!',
        ]);
    }

    public function unpublish(Curation $curation)
    {
        $user = Auth::user();
        abort_unless($curation->author_user_id === $user->id, 403);
        abort_unless($curation->status === 'approved', 403);

        $curation->update([
            'status' => 'draft',
            'approved_snapshot' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Curation $curation)
    {
        $user = Auth::user();
        abort_unless($curation->author_user_id === $user->id, 403);
        abort_unless(in_array($curation->status, ['draft', 'rejected']), 403);

        foreach ($curation->places as $p) {
            if ($p->photos) {
                foreach ($p->photos as $path) {
                    Storage::disk('public')->delete($path);
                    Storage::disk('public')->delete(ImageProcessor::thumbPathFor($path));
                }
            }
        }
        $curation->delete();

        return response()->json(['success' => true]);
    }

    public function report(Request $request, Curation $curation)
    {
        $data = $request->validate([
            'reason' => 'required|string|in:spam,inappropriate,copyright,false_info,other',
            'detail' => 'nullable|string|max:500',
        ]);

        CurationReport::create([
            'curation_id' => $curation->id,
            'reporter_user_id' => Auth::id(),
            'reason' => $data['reason'],
            'detail' => $data['detail'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    private function buildSnapshot(Curation $curation): array
    {
        $curation->load('places');
        return [
            'title' => $curation->title,
            'description' => $curation->description,
            'category' => $curation->category,
            'region_label' => $curation->region_label,
            'cover_image' => $curation->cover_image,
            'places' => $curation->places->map(fn($p) => $p->toArray())->toArray(),
        ];
    }
}
