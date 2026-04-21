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

        $this->generateMapThumbnail($place);
        $this->tryMatchNaverPlaceId($place);

        return redirect('/')->with('success', '장소가 저장되었어요.');
    }

    public function show(Place $place)
    {
        abort_unless($place->user_id === request()->user()?->id, 403);
        $place->load(['images', 'themes']);
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
            'places.*.category_name' => ['nullable', 'string', 'max:255'],
            'places.*.phone' => ['nullable', 'string', 'max:50'],
            'places.*.address' => ['nullable', 'string', 'max:255'],
            'places.*.road_address' => ['nullable', 'string', 'max:255'],
            'places.*.lat' => ['nullable', 'numeric'],
            'places.*.lng' => ['nullable', 'numeric'],
            'places.*.memo' => ['nullable', 'string', 'max:500'],
            'places.*.status' => ['nullable', 'in:planned,visited'],
            'places.*.visited_at' => ['nullable'],
            'places.*.is_overseas' => ['nullable', 'boolean'],
        ]);

        CategoryController::ensureUserCategories($user);
        $userCats = Category::where('user_id', $user->id)->get()->keyBy(fn ($c) => mb_strtolower($c->name));

        $maxSort = (int) Place::where('user_id', $user->id)->max('sort_order');
        $imported = 0;

        foreach ($data['places'] as $p) {
            $catName = isset($p['category_name']) ? mb_strtolower($p['category_name']) : '';
            $catId = $userCats[$catName]->id ?? null;

            $lat = isset($p['lat']) && $p['lat'] !== '' ? (float) $p['lat'] : null;
            $lng = isset($p['lng']) && $p['lng'] !== '' ? (float) $p['lng'] : null;
            $visited = !empty($p['visited_at']) ? substr($p['visited_at'], 0, 10) : null;

            Place::create([
                'user_id' => $user->id,
                'category_id' => $catId,
                'name' => $p['name'],
                'phone' => $p['phone'] ?? null,
                'address' => $p['address'] ?? null,
                'road_address' => $p['road_address'] ?? null,
                'lat' => $lat,
                'lng' => $lng,
                'memo' => $p['memo'] ?? null,
                'status' => $p['status'] ?? 'planned',
                'visited_at' => $visited,
                'is_overseas' => !empty($p['is_overseas']),
                'sort_order' => ++$maxSort,
                'is_visible' => true,
                'is_public' => false,
            ]);
            $imported++;
        }

        return response()->json(['ok' => true, 'imported' => $imported]);
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

        if (
            ($place->lat !== null && $place->lng !== null) &&
            ($oldLat != $place->lat || $oldLng != $place->lng || empty($place->thumbnail))
        ) {
            $this->generateMapThumbnail($place);
        }

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

        $this->tryMatchNaverPlaceId($place);

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

    public function destroyImage(PlaceImage $placeImage, Request $request)
    {
        $place = $placeImage->place;
        abort_unless($place->user_id === $request->user()?->id, 403);

        Storage::disk('public')->delete([$placeImage->path, $placeImage->thumb_path]);
        $placeImage->delete();

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

    // 테마별 내 장소 (로그인 사용자)
    public function placesByTheme(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['items' => []]);
        }
        $slug = trim((string) $request->input('theme', ''));
        $query = Place::where('user_id', $request->user()->id)
            ->where('is_visible', true)
            ->with(['category', 'themes']);

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
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.internationalPhoneNumber,places.location,places.primaryType,places.regularOpeningHours',
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
                'opening_hours' => $p['regularOpeningHours']['weekdayDescriptions'] ?? null,
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
        $relPath = "static-maps/{$hash}.jpg";
        $absPath = storage_path('app/public/' . $relPath);

        if (!is_file($absPath)) {
            if (!is_dir(dirname($absPath))) {
                @mkdir(dirname($absPath), 0775, true);
            }
            try {
                $body = $overseas
                    ? $this->fetchGoogleStaticMap($lat, $lng, $w, $h)
                    : $this->fetchNaverStaticMap($lat, $lng, $w, $h);
                if ($body) {
                    file_put_contents($absPath, $body);
                }
            } catch (\Throwable $e) {
                Log::warning('[static-map] fetch fail: ' . $e->getMessage());
            }
        }

        if (is_file($absPath)) {
            return response()->file($absPath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=2592000', // 30일
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
        if ($name === '') return response()->json(['phone' => '', 'opening_hours' => null, 'source' => null]);

        $info = $this->infoFromGooglePlaces($name, $address);
        if ($info['phone'] || $info['opening_hours']) {
            return response()->json([
                'phone' => $info['phone'],
                'opening_hours' => $info['opening_hours'],
                'source' => 'google',
            ]);
        }

        return response()->json(['phone' => '', 'opening_hours' => null, 'source' => null]);
    }

    private function infoFromGooglePlaces(string $name, string $address): array
    {
        $empty = ['phone' => '', 'opening_hours' => null];
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
                if (!$tel && !$hours) continue;
                if ($title && $normName && (str_contains($title, $normName) || str_contains($normName, $title))) {
                    return ['phone' => $tel, 'opening_hours' => $hours];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Places lookup error', ['msg' => $e->getMessage()]);
        }
        return $empty;
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
