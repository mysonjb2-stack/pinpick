<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Services\AddressParserService;
use App\Services\GoogleReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CollectorController extends Controller
{
    public function index()
    {
        return view('admin.collector.index');
    }

    public function extractFromYoutube(Request $request)
    {
        $request->validate(['url' => 'required|string']);

        $videoId = $this->parseYoutubeId($request->url);
        if (!$videoId) {
            return response()->json(['error' => '유효한 유튜브 URL이 아닙니다.'], 422);
        }

        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'YouTube API 키가 설정되지 않았습니다.'], 500);
        }

        $resp = Http::timeout(8)->get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet',
            'id' => $videoId,
            'key' => $apiKey,
        ]);

        if (!$resp->successful() || empty($resp->json()['items'])) {
            return response()->json(['error' => '영상 정보를 가져올 수 없습니다.'], 422);
        }

        $snippet = $resp->json()['items'][0]['snippet'];
        $description = $snippet['description'] ?? '';

        $chapters = $this->parseChapters($description);

        $pinnedComment = $this->fetchPinnedComment($videoId, $apiKey);

        $textParts = [$snippet['title'], $description];
        if ($pinnedComment) {
            $textParts[] = "--- 고정 댓글 ---\n" . $pinnedComment;
        }
        $text = implode("\n\n", $textParts);

        $places = $this->extractPlaces($text, $chapters);
        if ($places === null) {
            return response()->json(['error' => '장소 추출에 실패했습니다.'], 500);
        }

        $detectedCountry = $this->detectCountryFromPlaces($places);
        $detectedRegion = $this->detectRegionFromPlaces($places);
        $isOverseas = $detectedCountry && $detectedCountry !== 'KR';

        $matched = $this->matchPlaces($places, $isOverseas, $detectedCountry, $detectedRegion);

        $hasCourse = collect($places)->contains(fn($p) => !empty($p['day']));

        return response()->json([
            'source' => [
                'type' => 'youtube',
                'channel' => $snippet['channelTitle'] ?? '',
                'date' => substr($snippet['publishedAt'] ?? '', 0, 10),
                'url' => 'https://www.youtube.com/watch?v=' . $videoId,
                'title' => $snippet['title'] ?? '',
            ],
            'has_chapters' => !empty($chapters),
            'has_pinned_comment' => !empty($pinnedComment),
            'detected_type' => $hasCourse ? 'course' : 'list',
            'detected_country' => $detectedCountry,
            'detected_region' => $detectedRegion,
            'is_overseas' => $isOverseas,
            'places' => $matched,
        ]);
    }

    public function extractFromText(Request $request)
    {
        $request->validate(['text' => 'required|string|min:10']);

        $places = $this->extractPlaces($request->text);
        if ($places === null) {
            return response()->json(['error' => '장소 추출에 실패했습니다.'], 500);
        }

        $detectedCountry = $this->detectCountryFromPlaces($places);
        $detectedRegion = $this->detectRegionFromPlaces($places);
        $isOverseas = $detectedCountry && $detectedCountry !== 'KR';

        $matched = $this->matchPlaces($places, $isOverseas, $detectedCountry, $detectedRegion);

        $hasCourse = collect($places)->contains(fn($p) => !empty($p['day']));

        return response()->json([
            'source' => ['type' => 'text'],
            'detected_type' => $hasCourse ? 'course' : 'list',
            'detected_country' => $detectedCountry,
            'detected_region' => $detectedRegion,
            'is_overseas' => $isOverseas,
            'places' => $matched,
        ]);
    }

    public function searchKakao(Request $request)
    {
        $request->validate(['query' => 'required|string']);

        $results = $this->kakaoKeywordSearch($request->query('query'));

        return response()->json(['results' => $results]);
    }

    public function createDraft(Request $request)
    {
        $request->validate([
            'places' => 'required|array|min:1',
            'places.*.place_name' => 'required|string',
            'places.*.address' => 'nullable|string',
            'places.*.latitude' => 'required|numeric',
            'places.*.longitude' => 'required|numeric',
            'places.*.category_label' => 'nullable|string',
            'places.*.external_place_id' => 'nullable|string',
            'places.*.google_place_id' => 'nullable|string',
            'places.*.source_channel' => 'nullable|string',
            'places.*.source_url' => 'nullable|string',
            'places.*.source_date' => 'nullable|date',
            'places.*.editor_note' => 'nullable|string',
            'places.*.phone' => 'nullable|string',
            'places.*.day_number' => 'nullable|integer',
            'places.*.sort_order' => 'nullable|integer',
            'places.*.transit_hint' => 'nullable|string|max:100',
            'source_title' => 'nullable|string',
            'category' => 'nullable|string',
            'draft_type' => 'nullable|string|in:list,course',
            'nights' => 'nullable|integer|min:0|max:30',
            'days' => 'nullable|integer|min:1|max:31',
        ]);

        $cats = array_keys(config('curation_categories'));
        $category = in_array($request->category, $cats) ? $request->category : ($cats[0] ?? 'food');

        $title = $request->source_title ?: '수집 도우미 초안 ' . now()->format('m/d H:i');
        $draftType = $request->draft_type ?: 'list';

        $parser = app(AddressParserService::class);

        $placeNames = collect($request->places)->pluck('place_name')->filter()->toArray();
        $description = '';
        if (!empty($placeNames)) {
            $listed = array_slice($placeNames, 0, 5);
            $description = implode(', ', $listed);
            if (count($placeNames) > 5) {
                $description .= ' 외 ' . (count($placeNames) - 5) . '곳';
            }
        }

        $daysVal = null;
        $nightsVal = null;
        if ($draftType === 'course') {
            $daysVal = $request->days;
            $nightsVal = $request->nights;
            if ($daysVal === null) {
                $maxDay = collect($request->places)->max('day_number');
                if ($maxDay) $daysVal = (int) $maxDay;
            }
            if ($nightsVal === null && $daysVal) {
                $nightsVal = max(0, $daysVal - 1);
            }
        }

        $curation = Curation::create([
            'title' => $title,
            'slug' => Curation::generateSlug($title),
            'status' => 'draft',
            'type' => $draftType,
            'nights' => $nightsVal,
            'days' => $daysVal,
            'category' => $category,
            'description' => $description,
        ]);

        $googleService = app(GoogleReviewService::class);

        $sortedPlaces = collect($request->places)->sort(function ($a, $b) {
            $da = $a['day_number'] ?? PHP_INT_MAX;
            $db = $b['day_number'] ?? PHP_INT_MAX;
            if ($da !== $db) return $da <=> $db;
            return ($a['sort_order'] ?? PHP_INT_MAX) <=> ($b['sort_order'] ?? PHP_INT_MAX);
        })->values()->all();

        foreach ($sortedPlaces as $i => $p) {
            $isOverseas = ($p['latitude'] ?? 0) < 30 || ($p['latitude'] ?? 0) > 44
                || ($p['longitude'] ?? 0) < 124 || ($p['longitude'] ?? 0) > 132;

            $placeData = [
                'curation_id' => $curation->id,
                'place_name' => $p['place_name'],
                'address' => $p['address'] ?? null,
                'latitude' => $p['latitude'],
                'longitude' => $p['longitude'],
                'category_label' => $p['category_label'] ?? null,
                'external_place_id' => $p['external_place_id'] ?? null,
                'is_overseas' => $isOverseas,
                'source_channel' => $p['source_channel'] ?? null,
                'source_url' => $p['source_url'] ?? null,
                'source_date' => $p['source_date'] ?? null,
                'editor_note' => $p['editor_note'] ?? null,
                'phone' => $p['phone'] ?? null,
                'day_number' => $p['day_number'] ?? null,
                'sort_order' => $i,
                'transit_hint' => $p['transit_hint'] ?? null,
            ];

            if (!$isOverseas) {
                $placeData['dong_label'] = $this->parseDongFromAddress($p['address'] ?? '');
            }

            $region = $parser->parse($p['address'] ?? null, $isOverseas);
            AddressParserService::applyCanonicalDisplay($region);
            $placeData['country_code'] = $region['country_code'];
            $placeData['region_l1'] = $region['region_l1'];
            $placeData['region_l2'] = $region['region_l2'];
            $placeData['region_l1_key'] = $region['region_l1_key'] ?? null;
            $placeData['region_l2_key'] = $region['region_l2_key'] ?? null;

            $preGid = $p['google_place_id'] ?? null;
            if ($preGid) {
                $placeData['google_place_id'] = $preGid;
            }

            $place = CurationPlace::create($placeData);

            if ($preGid) {
                try { $googleService->fetchAndCache($preGid); } catch (\Throwable $e) {}
            } else {
                try {
                    $gid = $googleService->matchPlaceId(
                        $p['place_name'],
                        (float) $p['latitude'],
                        (float) $p['longitude'],
                        $p['address'] ?? null
                    );
                    if ($gid) {
                        $place->update(['google_place_id' => $gid]);
                        $googleService->fetchAndCache($gid);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Collector: Google match failed', ['place' => $p['place_name'], 'error' => $e->getMessage()]);
                }
            }
        }

        $curation->refreshCenter();
        $curation->refreshRegionCodes();

        return response()->json([
            'success' => true,
            'redirect' => route('admin.curations.edit', $curation),
        ]);
    }

    private function parseYoutubeId(string $url): ?string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    private function parseChapters(string $description): array
    {
        $chapters = [];
        $lines = explode("\n", $description);

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(\d{1,2}:\d{2}(?::\d{2})?)\s+(.+)$/', $line, $m)) {
                $chapters[] = [
                    'timestamp' => $m[1],
                    'title' => trim($m[2]),
                ];
            }
        }

        return $chapters;
    }

    private function fetchPinnedComment(string $videoId, string $apiKey): ?string
    {
        try {
            $resp = Http::timeout(8)->get('https://www.googleapis.com/youtube/v3/commentThreads', [
                'part' => 'snippet',
                'videoId' => $videoId,
                'order' => 'relevance',
                'maxResults' => 5,
                'key' => $apiKey,
            ]);

            if (!$resp->successful()) return null;

            $items = $resp->json()['items'] ?? [];
            if (empty($items)) return null;

            foreach ($items as $item) {
                $topLevel = $item['snippet']['topLevelComment']['snippet'] ?? [];
                if (!empty($topLevel['textOriginal'])) {
                    return mb_substr($topLevel['textOriginal'], 0, 4000);
                }
            }
        } catch (\Throwable $e) {
            Log::info('Collector: Comment fetch failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function extractPlaces(string $text, array $chapters = []): ?array
    {
        $apiKey = config('services.anthropic.api_key');
        if (!$apiKey) {
            Log::error('Collector: ANTHROPIC_API_KEY not set');
            return null;
        }

        $chapterSection = '';
        if (!empty($chapters)) {
            $chapterLines = array_map(
                fn($c) => $c['timestamp'] . ' ' . $c['title'],
                $chapters
            );
            $chapterSection = "\n\n참고: 이 영상에는 챕터가 있습니다. 챕터 순서가 곧 방문 순서입니다.\n챕터 목록:\n" . implode("\n", $chapterLines);
        }

        $prompt = <<<'PROMPT'
아래 텍스트에서 실제 방문 가능한 장소(식당, 카페, 관광지, 숙소 등)를 추출해주세요.

규칙:
- 실제 상호명만 추출 (메뉴명, 브랜드 일반명, 지역명 단독은 제외)
- 지점명이 있으면 포함 (예: "스타벅스 강남점")
- 확실하지 않은 건 제외
- JSON 배열만 출력, 다른 텍스트 없이

국가·지역 감지 규칙:
- 텍스트 전체(제목, 설명, 챕터, 댓글)를 종합해 콘텐츠의 국가를 판단
- country_code: ISO 3166-1 alpha-2 (한국이면 "KR", 일본이면 "JP", 베트남이면 "VN" 등)
- region_hint: 검색 기준이 될 광역 지역명 (예: "오키나와", "다낭", "도쿄", "방콕")
- 판단 불가 시 null

다국어 장소명 규칙:
- name: 한국어 표기 (텍스트에 나온 그대로)
- name_local: 현지어 표기 (일본어, 베트남어 등. 예: 首里城, 國際通り). 텍스트에 없으면 알고 있는 범위에서 채우고, 모르면 null
- name_en: 영문 표기 (예: Shuri Castle, Kokusai Street). 텍스트에 없으면 알고 있는 범위에서 채우고, 모르면 null

일자(day) 인식 규칙:
- "1일차", "Day 1", "DAY1", "첫째 날", "첫째날", "첫날" → day: 1
- "2일차", "Day 2", "DAY2", "둘째 날", "둘째날" → day: 2
- "3일차", "Day 3", "DAY3", "셋째 날", "셋째날" → day: 3
- 이런 패턴으로 4일차 이상도 동일하게 처리
- 일자 표현이 전혀 없으면 day: null (1로 추정하지 말 것)

순서(order) 규칙:
- 같은 day 내에서의 등장 순서 (1부터 시작)
- 챕터가 있으면 챕터 순서를 따름
- day가 null이면 전체 텍스트 내 등장 순서

이동 정보(transit_hint) 규칙:
- 이전 장소에서의 이동 방법/시간이 언급되면 기록
- 예: "차로 20분", "도보 5분", "버스로 30분"
- 없으면 null

출력 형식:
[{"name":"상호명","name_local":"現地語名","name_en":"English Name","country_code":"JP","region_hint":"지역힌트","mention_context":"언급 맥락 한 줄","day":null,"order":1,"transit_hint":null}]

장소가 없으면 빈 배열 []을 반환.
PROMPT;

        $fullPrompt = $prompt . $chapterSection;

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $resp = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 4096,
                    'messages' => [
                        ['role' => 'user', 'content' => $fullPrompt . "\n\n---\n\n" . mb_substr($text, 0, 8000)],
                    ],
                ]);

                if (!$resp->successful()) {
                    Log::warning('Collector: Claude API error', ['status' => $resp->status(), 'body' => $resp->body()]);
                    continue;
                }

                $content = $resp->json()['content'][0]['text'] ?? '';
                if (preg_match('/\[.*\]/s', $content, $m)) {
                    $parsed = json_decode($m[0], true);
                    if (is_array($parsed)) {
                        return $parsed;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Collector: Claude API exception', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function matchPlaces(array $places, bool $isOverseas, ?string $countryCode, ?string $regionHint, ?array $viewport = null): array
    {
        $results = [];
        foreach ($places as $p) {
            $name = $p['name'] ?? '';
            $nameLocal = $p['name_local'] ?? null;
            $nameEn = $p['name_en'] ?? null;
            $hint = $p['region_hint'] ?? $regionHint ?? '';
            $context = $p['mention_context'] ?? '';

            if (!$isOverseas) {
                $query = trim($hint . ' ' . $name);
                $kakaoResults = $this->kakaoKeywordSearch($query);
                $results[] = [
                    'extracted_name' => $name,
                    'name_local' => $nameLocal,
                    'name_en' => $nameEn,
                    'region_hint' => $hint,
                    'mention_context' => $context,
                    'day' => $p['day'] ?? null,
                    'order' => $p['order'] ?? null,
                    'transit_hint' => $p['transit_hint'] ?? null,
                    'matches' => array_slice($kakaoResults, 0, 3),
                    'match_stage' => !empty($kakaoResults) ? 'ko' : null,
                ];
                continue;
            }

            $matched = [];
            $stage = null;

            $matched = $this->googleTextSearch($name, $countryCode, $viewport);
            if (!empty($matched)) {
                $stage = 'ko';
            }

            if (empty($matched) && $nameLocal) {
                $matched = $this->googleTextSearch($nameLocal, $countryCode, $viewport);
                if (!empty($matched)) $stage = 'local';
            }

            if (empty($matched) && $nameEn) {
                $matched = $this->googleTextSearch($nameEn, $countryCode, $viewport);
                if (!empty($matched)) $stage = 'en';
            }

            if (empty($matched) && $hint) {
                $contextQuery = trim($hint . ' ' . $name);
                $matched = $this->googleTextSearch($contextQuery, $countryCode, $viewport);
                if (!empty($matched)) $stage = 'context';
            }

            $results[] = [
                'extracted_name' => $name,
                'name_local' => $nameLocal,
                'name_en' => $nameEn,
                'region_hint' => $hint,
                'mention_context' => $context,
                'day' => $p['day'] ?? null,
                'order' => $p['order'] ?? null,
                'transit_hint' => $p['transit_hint'] ?? null,
                'matches' => array_slice($matched, 0, 3),
                'match_stage' => $stage,
            ];

            if ($isOverseas) usleep(100_000);
        }

        return $results;
    }

    private function matchPlacesKakao(array $places): array
    {
        return $this->matchPlaces($places, false, 'KR', null);
    }

    private function kakaoKeywordSearch(string $query): array
    {
        $apiKey = config('services.kakao_local.rest_api_key');
        if (!$apiKey || !$query) return [];

        try {
            $resp = Http::withHeaders([
                'Authorization' => 'KakaoAK ' . $apiKey,
            ])->timeout(5)->get('https://dapi.kakao.com/v2/local/search/keyword.json', [
                'query' => $query,
                'size' => 5,
            ]);

            if (!$resp->successful()) return [];

            return collect($resp->json()['documents'] ?? [])
                ->map(fn($d) => [
                    'place_name' => $d['place_name'],
                    'address' => $d['road_address_name'] ?: $d['address_name'],
                    'latitude' => (float) $d['y'],
                    'longitude' => (float) $d['x'],
                    'category_label' => $this->shortenCategory($d['category_name'] ?? ''),
                    'external_place_id' => $d['id'] ?? null,
                    'phone' => $d['phone'] ?? null,
                ])
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('Collector: Kakao search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function googleTextSearch(string $query, ?string $countryCode = null, ?array $viewport = null): array
    {
        $key = config('services.google_places.api_key');
        if (!$key || !$query) return [];

        $body = [
            'textQuery' => $query,
            'languageCode' => 'ko',
            'maxResultCount' => 5,
        ];

        if ($countryCode) {
            $body['regionCode'] = $countryCode;
        }

        if ($viewport && isset($viewport['low']) && isset($viewport['high'])) {
            $body['locationRestriction'] = [
                'rectangle' => [
                    'low' => ['latitude' => (float) $viewport['low']['lat'], 'longitude' => (float) $viewport['low']['lng']],
                    'high' => ['latitude' => (float) $viewport['high']['lat'], 'longitude' => (float) $viewport['high']['lng']],
                ],
            ];
        }

        try {
            $resp = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.internationalPhoneNumber,places.location,places.primaryType',
            ])->timeout(8)->post('https://places.googleapis.com/v1/places:searchText', $body);

            if (!$resp->successful()) return [];

            $places = $resp->json()['places'] ?? [];

            return collect($places)->map(fn($p) => [
                'place_name' => $p['displayName']['text'] ?? '',
                'address' => $p['formattedAddress'] ?? '',
                'latitude' => (float) ($p['location']['latitude'] ?? 0),
                'longitude' => (float) ($p['location']['longitude'] ?? 0),
                'category_label' => $p['primaryType'] ?? '',
                'external_place_id' => null,
                'google_place_id' => $p['id'] ?? null,
                'phone' => $p['internationalPhoneNumber'] ?? null,
            ])->toArray();
        } catch (\Throwable $e) {
            Log::warning('Collector: Google search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function geocodeRegion(Request $request)
    {
        $request->validate(['region' => 'required|string', 'country_code' => 'nullable|string|max:2']);

        $key = config('services.google_maps.api_key');
        if (!$key) return response()->json(['error' => 'Google Maps API key not set'], 500);

        $params = [
            'address' => $request->region,
            'key' => $key,
            'language' => 'ko',
        ];
        if ($request->country_code) {
            $params['components'] = 'country:' . $request->country_code;
        }

        $resp = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/geocode/json', $params);
        $data = $resp->json();
        $result = $data['results'][0] ?? null;

        if (!$result) return response()->json(['found' => false]);

        $vp = $result['geometry']['viewport'] ?? null;
        $loc = $result['geometry']['location'] ?? null;
        $name = $result['formatted_address'] ?? $request->region;

        return response()->json([
            'found' => true,
            'name' => $name,
            'location' => $loc,
            'viewport' => $vp ? [
                'low' => ['lat' => $vp['southwest']['lat'], 'lng' => $vp['southwest']['lng']],
                'high' => ['lat' => $vp['northeast']['lat'], 'lng' => $vp['northeast']['lng']],
            ] : null,
        ]);
    }

    public function searchGoogle(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'country_code' => 'nullable|string|max:2',
            'viewport' => 'nullable|array',
        ]);

        $results = $this->googleTextSearch(
            $request->query('query'),
            $request->query('country_code'),
            $request->query('viewport') ? json_decode($request->query('viewport'), true) : null
        );

        return response()->json(['results' => $results]);
    }

    public function rematchAll(Request $request)
    {
        $request->validate([
            'places' => 'required|array',
            'is_overseas' => 'required|boolean',
            'country_code' => 'nullable|string|max:2',
            'region_hint' => 'nullable|string',
            'viewport' => 'nullable|array',
        ]);

        $matched = $this->matchPlaces(
            $request->places,
            $request->is_overseas,
            $request->country_code,
            $request->region_hint,
            $request->viewport
        );

        return response()->json(['places' => $matched]);
    }

    private function detectCountryFromPlaces(array $places): ?string
    {
        $codes = [];
        foreach ($places as $p) {
            $cc = $p['country_code'] ?? null;
            if ($cc) $codes[] = $cc;
        }
        if (empty($codes)) return null;

        $counts = array_count_values($codes);
        arsort($counts);
        return array_key_first($counts);
    }

    private function detectRegionFromPlaces(array $places): ?string
    {
        $hints = [];
        foreach ($places as $p) {
            $h = $p['region_hint'] ?? null;
            if ($h) $hints[] = $h;
        }
        if (empty($hints)) return null;

        $counts = array_count_values($hints);
        arsort($counts);
        return array_key_first($counts);
    }

    private function parseDongFromAddress(string $address): ?string
    {
        $parts = preg_split('/\s+/', trim($address));
        foreach ($parts as $part) {
            if (preg_match('/(동|읍|면|리)$/', $part)) {
                $cleaned = preg_replace('/\d+(동)$/', '$1', $part);
                $name = preg_replace('/(동|읍|면|리)$/', '', $cleaned);
                return ($name !== '' && mb_strlen($name) >= 2) ? $name : $cleaned;
            }
        }
        return null;
    }

    private function shortenCategory(string $cat): string
    {
        $parts = explode(' > ', $cat);
        return end($parts) ?: $cat;
    }

    private function buildRegionLabel(array $places): string
    {
        $regions = [];
        foreach ($places as $p) {
            $addr = $p['address'] ?? '';
            if (!$addr) continue;
            $parts = preg_split('/\s+/', $addr);
            $sido = $parts[0] ?? '';
            $sigungu = $parts[1] ?? '';
            $short = $this->shortenSido($sido);
            $key = $short . ' ' . $sigungu;
            if ($short && $sigungu && !in_array($key, $regions)) {
                $regions[] = $key;
            }
        }

        return implode(', ', array_slice($regions, 0, 4));
    }

    private function shortenSido(string $sido): string
    {
        $map = [
            '서울특별시' => '서울', '부산광역시' => '부산', '대구광역시' => '대구',
            '인천광역시' => '인천', '광주광역시' => '광주', '대전광역시' => '대전',
            '울산광역시' => '울산', '세종특별자치시' => '세종',
            '경기도' => '경기', '경기' => '경기',
            '강원특별자치도' => '강원', '강원도' => '강원',
            '충청북도' => '충북', '충청남도' => '충남',
            '전라북도' => '전북', '전북특별자치도' => '전북',
            '전라남도' => '전남', '경상북도' => '경북', '경상남도' => '경남',
            '제주특별자치도' => '제주',
        ];
        return $map[$sido] ?? $sido;
    }
}
