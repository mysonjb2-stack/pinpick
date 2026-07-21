<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Services\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CurationController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $curations = Curation::withCount('places')
            ->when($q, fn($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.curations.index', compact('curations', 'q'));
    }

    public function create()
    {
        return view('admin.curations.form', ['curation' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:list,course',
            'description' => 'nullable|string|max:2000',
            'region_label' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data['slug'] = Curation::generateSlug($data['title']);
        $data['status'] = 'draft';

        if ($request->hasFile('cover_image')) {
            $proc = app(ImageProcessor::class);
            $data['cover_image'] = $proc->processPlaceImage($request->file('cover_image'), 'curations/covers');
        }

        $curation = Curation::create($data);

        return redirect()->route('admin.curations.edit', $curation)
            ->with('success', '큐레이션이 생성되었습니다.');
    }

    public function edit(Curation $curation)
    {
        $curation->load('places');
        return view('admin.curations.form', compact('curation'));
    }

    public function update(Request $request, Curation $curation)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:list,course',
            'description' => 'nullable|string|max:2000',
            'region_label' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $proc = app(ImageProcessor::class);
            $data['cover_image'] = $proc->processPlaceImage($request->file('cover_image'), 'curations/covers');
        }

        $curation->update($data);

        return redirect()->route('admin.curations.edit', $curation)
            ->with('success', '저장되었습니다.');
    }

    public function destroy(Curation $curation)
    {
        $curation->delete();
        return redirect()->route('admin.curations.index')
            ->with('success', '삭제되었습니다.');
    }

    public function togglePublish(Curation $curation)
    {
        if ($curation->status === 'published') {
            $curation->update(['status' => 'draft', 'published_at' => null]);
            return back()->with('success', '발행이 취소되었습니다.');
        }

        if ($curation->places()->count() === 0) {
            return back()->with('error', '장소가 없는 큐레이션은 발행할 수 없습니다.');
        }

        if (!$curation->cover_image) {
            $this->generateMosaicCover($curation);
        }

        $curation->update(['status' => 'published', 'published_at' => now()]);
        return back()->with('success', '발행되었습니다.');
    }

    private function generateMosaicCover(Curation $curation): void
    {
        $photos = [];
        foreach ($curation->places()->orderBy('sort_order')->get() as $p) {
            if ($p->photos && is_array($p->photos)) {
                foreach ($p->photos as $photo) {
                    $photos[] = $photo;
                    if (count($photos) >= 4) break 2;
                }
            }
        }

        if (empty($photos)) return;

        $proc = app(ImageProcessor::class);
        $cover = $proc->createMosaic($photos, 'curations/covers');
        if ($cover) {
            $curation->update(['cover_image' => $cover]);
        }
    }

    public function addPlace(Request $request, Curation $curation)
    {
        $data = $request->validate([
            'place_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'category_label' => 'nullable|string|max:50',
            'thumbnail_url' => 'nullable|string|max:500',
            'external_place_id' => 'nullable|string|max:255',
            'is_overseas' => 'nullable|boolean',
            'source_channel' => 'nullable|string|max:255',
            'source_url' => 'nullable|string|max:500',
            'source_date' => 'nullable|date',
            'day_number' => 'nullable|integer|min:1',
            'editor_note' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'opening_hours' => 'nullable',
            'building_name' => 'nullable|string|max:100',
            'naver_place_id' => 'nullable|string',
            'google_place_id' => 'nullable|string|max:255',
        ]);

        $data['curation_id'] = $curation->id;
        $data['sort_order'] = ($curation->places()->max('sort_order') ?? -1) + 1;
        $data['is_overseas'] = (bool) ($data['is_overseas'] ?? false);

        $place = CurationPlace::create($data);

        return response()->json(['success' => true, 'place' => $place]);
    }

    public function uploadPlacePhotos(Request $request, CurationPlace $place)
    {
        $request->validate([
            'photos' => 'required|array|max:3',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $proc = app(ImageProcessor::class);
        $dir = 'curations/' . $place->curation_id;
        $existing = $place->photos ?? [];

        foreach ($request->file('photos') as $file) {
            if (count($existing) >= 3) break;
            $path = $proc->processPlaceImage($file, $dir);
            $existing[] = $path;
        }

        $place->update([
            'photos' => $existing,
            'thumbnail_url' => asset('storage/' . ImageProcessor::thumbPathFor($existing[0])),
        ]);

        $photoUrls = array_map(fn($p) => [
            'path' => $p,
            'thumb' => asset('storage/' . ImageProcessor::thumbPathFor($p)),
        ], $existing);

        return response()->json(['success' => true, 'photos' => $photoUrls]);
    }

    public function deletePlacePhoto(Request $request, CurationPlace $place)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        $idx = $request->index;
        $photos = $place->photos ?? [];

        if (!isset($photos[$idx])) {
            return response()->json(['success' => false, 'error' => 'invalid index'], 422);
        }

        $path = $photos[$idx];
        Storage::disk('public')->delete($path);
        Storage::disk('public')->delete(ImageProcessor::thumbPathFor($path));

        array_splice($photos, $idx, 1);

        $update = ['photos' => empty($photos) ? null : array_values($photos)];
        if (empty($photos)) {
            $update['thumbnail_url'] = null;
        } else {
            $update['thumbnail_url'] = asset('storage/' . ImageProcessor::thumbPathFor($photos[0]));
        }

        $place->update($update);

        return response()->json(['success' => true]);
    }

    public function updatePlace(Request $request, CurationPlace $place)
    {
        $data = $request->validate([
            'source_channel' => 'nullable|string|max:255',
            'source_url' => 'nullable|string|max:500',
            'source_date' => 'nullable|date',
            'day_number' => 'nullable|integer|min:1',
            'editor_note' => 'nullable|string|max:255',
            'place_name' => 'nullable|string|max:255',
        ]);

        $place->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['success' => true]);
    }

    public function removePlace(CurationPlace $place)
    {
        if ($place->photos) {
            foreach ($place->photos as $path) {
                Storage::disk('public')->delete($path);
                Storage::disk('public')->delete(ImageProcessor::thumbPathFor($path));
            }
        }
        $place->delete();
        return response()->json(['success' => true]);
    }

    public function reorderPlaces(Request $request, Curation $curation)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($request->ids as $i => $id) {
            CurationPlace::where('id', $id)
                ->where('curation_id', $curation->id)
                ->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }
}
