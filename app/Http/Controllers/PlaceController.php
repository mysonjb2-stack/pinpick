<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use App\Models\PlaceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        return view('places.create', compact('categories', 'naverClientId', 'googleMapsKey'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'road_address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'memo' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
            'kakao_place_id' => ['nullable', 'string'],
            'is_overseas' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        $data['is_overseas'] = (bool) ($data['is_overseas'] ?? false);
        $data['user_id'] = $request->user()->id;

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

        foreach ($images as $i => $file) {
            $path = $file->store('places/' . $place->id, 'public');
            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }

        $this->generateMapThumbnail($place);

        return redirect('/')->with('success', '장소가 저장되었어요.');
    }

    public function show(Place $place)
    {
        abort_unless($place->user_id === request()->user()?->id, 403);
        $place->load('images');
        return view('places.show', compact('place'));
    }

    public function edit(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);
        $place->load('images');

        CategoryController::ensureUserCategories($request->user());
        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('sort_order')->get();
        $naverClientId = config('services.naver_map.client_id');
        $googleMapsKey = config('services.google_maps.api_key');

        return view('places.create', compact('place', 'categories', 'naverClientId', 'googleMapsKey'));
    }

    public function update(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'road_address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'memo' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
            'kakao_place_id' => ['nullable', 'string'],
            'is_overseas' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        $data['is_overseas'] = (bool) ($data['is_overseas'] ?? false);

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

        if (
            ($place->lat !== null && $place->lng !== null) &&
            ($oldLat != $place->lat || $oldLng != $place->lng || empty($place->thumbnail))
        ) {
            $this->generateMapThumbnail($place);
        }

        // 새 이미지 추가 (기존 이미지 수 + 신규 <= 5)
        $existingCount = $place->images()->count();
        foreach ($images as $i => $file) {
            if ($existingCount + $i + 1 > 5) break;
            $path = $file->store('places/' . $place->id, 'public');
            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'sort_order' => $existingCount + $i,
            ]);
        }

        return redirect()->route('places.show', $place)->with('success', '수정되었어요.');
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

    public function destroyImage(PlaceImage $placeImage, Request $request)
    {
        $place = $placeImage->place;
        abort_unless($place->user_id === $request->user()?->id, 403);

        Storage::disk('public')->delete($placeImage->path);
        $placeImage->delete();

        return response()->json(['ok' => true]);
    }

    public function destroy(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);
        foreach ($place->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        if ($place->thumbnail) {
            Storage::disk('public')->delete($place->thumbnail);
        }
        $place->delete();
        return redirect('/')->with('success', '삭제되었어요.');
    }

    // 카카오 로컬 API 프록시 (검색)
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['documents' => []]);

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'KakaoAK ' . $key,
        ])->get('https://dapi.kakao.com/v2/local/search/keyword.json', [
            'query' => $q,
            'size' => 15,
        ]);

        return response()->json($res->json());
    }

    // Google Places API (New) Text Search 프록시
    public function searchOverseas(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['documents' => []]);

        $key = config('services.google_places.api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.internationalPhoneNumber,places.location,places.primaryType',
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $q,
            'languageCode' => 'ko',
            'maxResultCount' => 15,
        ]);

        $data = $res->json();
        $places = $data['places'] ?? [];

        // 카카오 형식으로 통일 (프론트 코드 공유)
        $documents = array_map(function ($p) {
            return [
                'id' => $p['id'] ?? '',
                'place_name' => $p['displayName']['text'] ?? '',
                'road_address_name' => $p['formattedAddress'] ?? '',
                'address_name' => $p['formattedAddress'] ?? '',
                'phone' => $p['internationalPhoneNumber'] ?? '',
                'x' => (string) ($p['location']['longitude'] ?? ''),
                'y' => (string) ($p['location']['latitude'] ?? ''),
                'category_group_name' => $p['primaryType'] ?? '',
            ];
        }, $places);

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
            'X-Goog-FieldMask' => 'id,displayName,formattedAddress,internationalPhoneNumber,location,primaryType',
        ])->get("https://places.googleapis.com/v1/places/{$placeId}", [
            'languageCode' => 'ko',
        ]);

        $p = $res->json();

        return response()->json([
            'id' => $p['id'] ?? $placeId,
            'place_name' => $p['displayName']['text'] ?? '',
            'road_address_name' => $p['formattedAddress'] ?? '',
            'address_name' => $p['formattedAddress'] ?? '',
            'phone' => $p['internationalPhoneNumber'] ?? '',
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
        return response()->json(['address' => $road ?: $jibun]);
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

        return response()->json(['address' => $address]);
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

    // 전화번호 폴백: 카카오에서 못 찾은 국내 장소 phone을 구글 Places로 조회
    // (네이버 지역검색은 telephone 필드가 deprecated되어 항상 빈 값이라 사용 불가)
    public function phoneFallback(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $address = trim((string) $request->input('address', ''));
        if ($name === '') return response()->json(['phone' => '', 'source' => null]);

        $gg = $this->phoneFromGooglePlaces($name, $address);
        if ($gg) return response()->json(['phone' => $gg, 'source' => 'google']);

        return response()->json(['phone' => '', 'source' => null]);
    }

    private function phoneFromGooglePlaces(string $name, string $address): string
    {
        $key = config('services.google_places.api_key');
        if (!$key) return '';
        $query = trim($address !== '' ? ($address . ' ' . $name) : $name);
        try {
            $res = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'places.displayName,places.internationalPhoneNumber,places.nationalPhoneNumber,places.formattedAddress',
            ])->timeout(6)->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $query,
                'languageCode' => 'ko',
                'regionCode' => 'KR',
                'maxResultCount' => 3,
            ]);
            if (!$res->successful()) return '';
            $places = $res->json()['places'] ?? [];
            $normName = $this->normalizeName($name);
            foreach ($places as $p) {
                $title = $this->normalizeName($p['displayName']['text'] ?? '');
                $tel = trim($p['nationalPhoneNumber'] ?? ($p['internationalPhoneNumber'] ?? ''));
                if (!$tel) continue;
                if ($title && $normName && (str_contains($title, $normName) || str_contains($normName, $title))) {
                    return $tel;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Places phone lookup error', ['msg' => $e->getMessage()]);
        }
        return '';
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

            $path = 'places/thumb_' . $place->id . '.jpg';
            Storage::disk('public')->put($path, $binary);

            $place->thumbnail = $path;
            $place->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Map thumbnail generation error', ['msg' => $e->getMessage(), 'place_id' => $place->id]);
        }
    }
}
