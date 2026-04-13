<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use App\Models\PlaceImage;
use Illuminate\Http\Request;
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
        $images = $request->file('images', []);
        unset($data['images']);

        $place->update($data);

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
        return $this->reverseGeocodeNaver($lat, $lng);
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
}
