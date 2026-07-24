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
        $status = $request->get('status');
        $curations = Curation::withCount(['places', 'reports'])
            ->with('author:id,name')
            ->when($q, fn($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending') DESC")
            ->orderByDesc('updated_at')
            ->paginate(20);

        $pendingCount = Curation::where('status', 'pending')->count();

        return view('admin.curations.index', compact('curations', 'q', 'status', 'pendingCount'));
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
        if ($curation->status === 'approved') {
            $curation->update(['status' => 'draft', 'published_at' => null]);
            return back()->with('success', '발행이 취소되었습니다.');
        }

        if ($curation->places()->count() === 0) {
            return back()->with('error', '장소가 없는 큐레이션은 발행할 수 없습니다.');
        }

        $this->generateMosaicCover($curation);

        $curation->update(['status' => 'approved', 'published_at' => now()]);
        return back()->with('success', '발행되었습니다.');
    }

    public function approve(Curation $curation)
    {
        $this->generateMosaicCover($curation);
        $curation->update([
            'status' => 'approved',
            'published_at' => $curation->published_at ?: now(),
            'rejected_reason' => null,
            'approved_snapshot' => null,
        ]);
        return back()->with('success', '승인되었습니다. 탐색에 공개됩니다.');
    }

    public function reject(Request $request, Curation $curation)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $curation->update([
            'status' => 'rejected',
            'rejected_reason' => $request->reason,
        ]);
        return back()->with('success', '반려되었습니다.');
    }

    public function suspend(Curation $curation)
    {
        $curation->update([
            'status' => 'suspended',
            'approved_snapshot' => null,
        ]);
        return back()->with('success', '강제 비공개 처리되었습니다.');
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

        $result = ['success' => true, 'phone' => null, 'opening_hours' => null, 'naver_place_id' => null, 'detail_address' => null];

        $update = [];

        // 1) 네이버 검색 — 전화번호, 건물명, 지번주소, naver_place_id
        $clientId = config('services.naver_search.client_id');
        $clientSecret = config('services.naver_search.client_secret');
        if ($clientId && $clientSecret) {
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

                if ($response->successful()) {
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
                            $kakaoTokens = preg_split('/\s+/', trim($address));
                            $lastToken = end($kakaoTokens);
                            $pos = mb_strrpos($roadAddr, $lastToken);
                            if ($pos !== false) {
                                $extra = trim(mb_substr($roadAddr, $pos + mb_strlen($lastToken)));
                                if ($extra) $result['building_name'] = $extra;
                            }
                        }

                        $jibeon = trim(strip_tags($best['address'] ?? ''));
                        if ($jibeon) $result['jibeon_address'] = $jibeon;

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
                    }
                }
            } catch (\Throwable $e) {
                Log::info('curation enrichNaver failed', ['error' => $e->getMessage()]);
            }
        }

        // 2) Google Places — 영업시간, 전화번호(네이버에서 못 찾았으면), 상세주소
        $googleInfo = $this->infoFromGooglePlaces($name, $address ?: '');
        if ($googleInfo['opening_hours']) {
            $result['opening_hours'] = $googleInfo['opening_hours'];
            if (!$place->opening_hours) $update['opening_hours'] = $googleInfo['opening_hours'];
        }
        if ($googleInfo['phone'] && !$result['phone']) {
            $result['phone'] = $googleInfo['phone'];
            if (!$place->phone && !isset($update['phone'])) $update['phone'] = $googleInfo['phone'];
        }
        if ($googleInfo['detail_address']) {
            $result['detail_address'] = $googleInfo['detail_address'];
        }

        if (!empty($update)) {
            $place->update($update);
        }

        return response()->json($result);
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
            Log::warning('Curation Google Places lookup error', ['msg' => $e->getMessage()]);
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

    public function searchTourImages(Request $request, CurationPlace $place)
    {
        $apiKey = config('services.tour_api.key');
        if (!$apiKey) {
            return response()->json(['error' => 'TOUR_API_KEY가 설정되지 않았습니다.'], 422);
        }

        $keyword = preg_replace('/\s*(본점|지점|분점|센터|점)\s*$/u', '', trim($place->place_name));
        $keyword = preg_replace('/\s+.{1,3}점$/u', '', $keyword);

        $base = 'https://apis.data.go.kr/B551011/KorService2';

        try {
            $searchRes = Http::timeout(8)->get("{$base}/searchKeyword2", [
                'serviceKey' => $apiKey,
                'MobileOS' => 'ETC',
                'MobileApp' => 'Pinpick',
                '_type' => 'json',
                'keyword' => $keyword,
                'numOfRows' => 5,
                'pageNo' => 1,
            ]);

            if (!$searchRes->successful()) {
                return response()->json(['error' => 'TourAPI 검색 실패 (HTTP ' . $searchRes->status() . ')'], 502);
            }

            $body = $searchRes->json();
            $items = $body['response']['body']['items']['item'] ?? [];
            if (empty($items)) {
                return response()->json(['images' => [], 'message' => '공식 이미지를 찾지 못했어요']);
            }

            $contentId = null;
            foreach ($items as $item) {
                if ($place->latitude && $place->longitude && isset($item['mapy'], $item['mapx'])) {
                    $dist = $this->haversineDist($place->latitude, $place->longitude, (float)$item['mapy'], (float)$item['mapx']);
                    if ($dist < 1000) {
                        $contentId = $item['contentid'];
                        break;
                    }
                }
            }
            if (!$contentId) {
                $contentId = $items[0]['contentid'] ?? null;
            }
            if (!$contentId) {
                return response()->json(['images' => [], 'message' => '공식 이미지를 찾지 못했어요']);
            }

            $imageRes = Http::timeout(8)->get("{$base}/detailImage2", [
                'serviceKey' => $apiKey,
                'MobileOS' => 'ETC',
                'MobileApp' => 'Pinpick',
                '_type' => 'json',
                'contentId' => $contentId,
                'imageYN' => 'Y',
                'subImageYN' => 'Y',
                'numOfRows' => 20,
            ]);

            if (!$imageRes->successful()) {
                return response()->json(['error' => 'TourAPI 이미지 조회 실패'], 502);
            }

            $imgItems = $imageRes->json()['response']['body']['items']['item'] ?? [];
            if (empty($imgItems)) {
                return response()->json(['images' => [], 'message' => '공식 이미지를 찾지 못했어요']);
            }

            $images = [];
            foreach ($imgItems as $img) {
                $images[] = [
                    'original' => $img['originimgurl'] ?? $img['imgname'] ?? '',
                    'thumbnail' => $img['smallimageurl'] ?? $img['originimgurl'] ?? '',
                    'source' => '한국관광공사',
                    'license' => $img['cpyrhtDivCd'] ?? '',
                ];
            }

            return response()->json(['images' => $images]);

        } catch (\Throwable $e) {
            Log::warning('TourAPI search error', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'TourAPI 호출 실패: ' . $e->getMessage()], 502);
        }
    }
}
