<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Models\PlaceImage;
use App\Services\ImageProcessor;
use App\Services\NaverPlaceMatcher;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CurationController extends Controller
{
    private function storageUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/storage/' . $path;
    }

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
        $cats = implode(',', array_keys(config('curation_categories')));
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:list,course',
            'category' => "required|in:{$cats}",
            'description' => 'nullable|string|max:2000',
            'region_label' => 'nullable|string|max:255',
        ]);

        $data['slug'] = Curation::generateSlug($data['title']);
        $data['status'] = 'draft';

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
        $cats = implode(',', array_keys(config('curation_categories')));
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:list,course',
            'category' => "required|in:{$cats}",
            'description' => 'nullable|string|max:2000',
            'region_label' => 'nullable|string|max:255',
        ]);

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

        $this->generateMosaicCover($curation);

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
            'jibeon_address' => 'nullable|string|max:255',
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
            'photos' => 'required|array|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $proc = app(ImageProcessor::class);
        $dir = 'curations/' . $place->curation_id;
        $existing = $place->photos ?? [];

        foreach ($request->file('photos') as $file) {
            if (count($existing) >= 5) break;
            $path = $proc->processPlaceImage($file, $dir);
            $existing[] = $path;
        }

        $place->update([
            'photos' => $existing,
            'thumbnail_url' => $this->storageUrl(ImageProcessor::thumbPathFor($existing[0])),
        ]);

        $photoUrls = array_map(fn($p) => [
            'path' => $p,
            'thumb' => $this->storageUrl(ImageProcessor::thumbPathFor($p)),
        ], $existing);

        return response()->json(['success' => true, 'photos' => $photoUrls]);
    }

    public function uploadPlacePhotoFromUrl(Request $request, CurationPlace $place)
    {
        $request->validate(['url' => 'required|url|max:2000']);

        $existing = $place->photos ?? [];
        if (count($existing) >= 5) {
            return response()->json(['success' => false, 'error' => '최대 5장까지 등록할 수 있습니다.'], 422);
        }

        $url = $request->url;
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return response()->json(['success' => false, 'error' => 'http/https URL만 허용됩니다.'], 422);
        }

        if ($this->isInternalHost($parsed['host'] ?? '')) {
            return response()->json(['success' => false, 'error' => '내부 네트워크 접근이 차단되었습니다.'], 422);
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'PinpickBot/1.0'])
                ->get($url);
            if (!$response->successful()) {
                return response()->json(['success' => false, 'error' => '다운로드 실패 (HTTP ' . $response->status() . ')'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '다운로드 실패: 시간 초과 또는 연결 불가'], 422);
        }

        $body = $response->body();
        if (strlen($body) > 10 * 1024 * 1024) {
            return response()->json(['success' => false, 'error' => '이미지 크기가 10MB를 초과합니다.'], 422);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($body);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return response()->json(['success' => false, 'error' => '유효한 이미지가 아닙니다. (' . $mime . ')'], 422);
        }

        $ext = match ($mime) {
            'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg',
        };
        $tmpPath = tempnam(sys_get_temp_dir(), 'ppurl_') . '.' . $ext;
        file_put_contents($tmpPath, $body);

        try {
            $file = new UploadedFile($tmpPath, 'url_image.' . $ext, $mime, null, true);
            $proc = app(ImageProcessor::class);
            $path = $proc->processPlaceImage($file, 'curations/' . $place->curation_id);
            $existing[] = $path;

            $place->update([
                'photos' => $existing,
                'thumbnail_url' => $this->storageUrl(ImageProcessor::thumbPathFor($existing[0])),
            ]);
        } finally {
            @unlink($tmpPath);
        }

        $photoUrls = array_map(fn($p) => [
            'path' => $p,
            'thumb' => $this->storageUrl(ImageProcessor::thumbPathFor($p)),
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
        $this->deletePhotoFileIfUnreferenced($path);

        array_splice($photos, $idx, 1);

        $update = ['photos' => empty($photos) ? null : array_values($photos)];
        if (empty($photos)) {
            $update['thumbnail_url'] = null;
        } else {
            $update['thumbnail_url'] = $this->storageUrl(ImageProcessor::thumbPathFor($photos[0]));
        }

        $place->update($update);

        $remaining = empty($photos) ? [] : array_values($photos);
        $photoUrls = array_map(fn($p) => [
            'path' => $p,
            'thumb' => $this->storageUrl(ImageProcessor::thumbPathFor($p)),
        ], $remaining);

        return response()->json(['success' => true, 'photos' => $photoUrls]);
    }

    public function reorderPlacePhotos(Request $request, CurationPlace $place)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|min:0']);

        $photos = $place->photos ?? [];
        $newPhotos = [];
        foreach ($request->order as $idx) {
            if (!isset($photos[$idx])) {
                return response()->json(['success' => false, 'error' => 'Invalid index'], 422);
            }
            $newPhotos[] = $photos[$idx];
        }

        if (count($newPhotos) !== count($photos)) {
            return response()->json(['success' => false, 'error' => 'Order count mismatch'], 422);
        }

        $place->update([
            'photos' => $newPhotos,
            'thumbnail_url' => $this->storageUrl(ImageProcessor::thumbPathFor($newPhotos[0])),
        ]);

        $photoUrls = array_map(fn($p) => [
            'path' => $p,
            'thumb' => $this->storageUrl(ImageProcessor::thumbPathFor($p)),
        ], $newPhotos);

        return response()->json(['success' => true, 'photos' => $photoUrls]);
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
            'phone' => 'nullable|string|max:50',
            'building_name' => 'nullable|string|max:100',
            'opening_hours' => 'nullable|string|max:500',
        ]);

        $update = [];
        foreach ($data as $k => $v) {
            if ($v !== null) {
                $update[$k] = $v;
            }
        }
        if ($request->has('phone') && $request->phone === '') {
            $update['phone'] = null;
        }
        if ($request->has('building_name') && $request->building_name === '') {
            $update['building_name'] = null;
        }
        if ($request->has('opening_hours')) {
            $raw = $request->opening_hours;
            if ($raw === '' || $raw === null) {
                $update['opening_hours'] = null;
            } else {
                $decoded = json_decode($raw, true);
                $update['opening_hours'] = $decoded !== null ? $decoded : $raw;
            }
        }

        $place->update($update);

        return response()->json(['success' => true]);
    }

    public function enrichNaver(Request $request, CurationPlace $place)
    {
        $name = trim((string) $request->input('name', $place->place_name));
        $lat = (float) ($request->input('lat') ?: $place->latitude);
        $lng = (float) ($request->input('lng') ?: $place->longitude);
        $address = $request->input('address', $place->address);

        $result = ['success' => true, 'phone' => null, 'opening_hours' => null, 'naver_place_id' => null];

        $clientId = config('services.naver_search.client_id');
        $clientSecret = config('services.naver_search.client_secret');
        if (!$clientId || !$clientSecret) {
            return response()->json($result);
        }

        try {
            $query = $name;
            if ($address) {
                $parts = preg_split('/\s+/u', trim($address));
                if (is_array($parts) && count($parts) >= 2) {
                    $query = $name . ' ' . $parts[0] . ' ' . $parts[1];
                }
            }

            $response = Http::withHeaders([
                'X-Naver-Client-Id' => $clientId,
                'X-Naver-Client-Secret' => $clientSecret,
            ])->timeout(3)->get('https://openapi.naver.com/v1/search/local.json', [
                'query' => $query,
                'display' => 5,
            ]);

            if (!$response->successful()) {
                return response()->json($result);
            }

            $items = $response->json('items') ?? [];
            $best = null;
            $bestDist = INF;
            foreach ($items as $item) {
                $mx = (float) ($item['mapx'] ?? 0);
                $my = (float) ($item['mapy'] ?? 0);
                if ($mx <= 0 || $my <= 0) continue;
                if ($mx > 1000000) { $mx /= 1e7; $my /= 1e7; }
                $dist = $this->haversineDist($lat, $lng, $my, $mx);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best = $item;
                }
            }

            if ($best && $bestDist <= 300) {
                $phone = trim(strip_tags($best['telephone'] ?? ''));
                if ($phone) $result['phone'] = $phone;

                $roadAddr = $best['roadAddress'] ?? '';
                if ($roadAddr && $address) {
                    $extra = trim(str_replace($address, '', $roadAddr));
                    if (!$extra) {
                        $normalized = preg_replace('/\s+/', ' ', $address);
                        $extra = trim(str_replace($normalized, '', $roadAddr));
                    }
                    if ($extra) $result['building_name'] = $extra;
                }

                $jibeon = trim(strip_tags($best['address'] ?? ''));
                if ($jibeon) $result['jibeon_address'] = $jibeon;

                $update = [];
                if ($phone && !$place->phone) $update['phone'] = $phone;
                if (!empty($result['building_name']) && !$place->building_name) {
                    $update['building_name'] = $result['building_name'];
                }
                if ($jibeon && !$place->jibeon_address) {
                    $update['jibeon_address'] = $jibeon;
                }

                $matcher = app(NaverPlaceMatcher::class);
                $placeId = $matcher->match($name, $lat, $lng, $address);
                if ($placeId) {
                    $result['naver_place_id'] = $placeId;
                    $update['naver_place_id'] = $placeId;
                }

                if (!empty($update)) {
                    $place->update($update);
                }
            }
        } catch (\Throwable $e) {
            Log::info('curation enrichNaver failed', ['error' => $e->getMessage()]);
        }

        return response()->json($result);
    }

    public function removePlace(CurationPlace $place)
    {
        if ($place->photos) {
            foreach ($place->photos as $path) {
                $this->deletePhotoFileIfUnreferenced($path);
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

    private function deletePhotoFileIfUnreferenced(string $path): void
    {
        $referenced = PlaceImage::where('path', $path)->exists();
        if (!$referenced) {
            Storage::disk('public')->delete($path);
            Storage::disk('public')->delete(ImageProcessor::thumbPathFor($path));
        }
    }

    private function isInternalHost(string $host): bool
    {
        if (in_array(strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'])) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPrivateIp($host);
        }
        $ips = gethostbynamel($host);
        if (!$ips) return true;
        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) return true;
        }
        return false;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function haversineDist(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $R * asin(min(1.0, sqrt($a)));
    }
}
