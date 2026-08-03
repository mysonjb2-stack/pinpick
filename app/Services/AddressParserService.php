<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AddressParserService
{
    private const SIDO_MAP = [
        '서울특별시' => '서울', '서울시' => '서울', '서울' => '서울',
        '부산광역시' => '부산', '부산시' => '부산', '부산' => '부산',
        '대구광역시' => '대구', '대구시' => '대구', '대구' => '대구',
        '인천광역시' => '인천', '인천시' => '인천', '인천' => '인천',
        '광주광역시' => '광주', '광주시' => '광주', '광주' => '광주',
        '대전광역시' => '대전', '대전시' => '대전', '대전' => '대전',
        '울산광역시' => '울산', '울산시' => '울산', '울산' => '울산',
        '세종특별자치시' => '세종', '세종시' => '세종', '세종' => '세종',
        '경기도' => '경기', '경기' => '경기',
        '강원특별자치도' => '강원', '강원도' => '강원', '강원' => '강원',
        '충청북도' => '충북', '충북' => '충북',
        '충청남도' => '충남', '충남' => '충남',
        '전북특별자치도' => '전북', '전라북도' => '전북', '전북' => '전북',
        '전라남도' => '전남', '전남' => '전남',
        '경상북도' => '경북', '경북' => '경북',
        '경상남도' => '경남', '경남' => '경남',
        '제주특별자치도' => '제주', '제주도' => '제주', '제주' => '제주',
    ];

    private const CITY_STATES = ['SG', 'HK', 'MO', 'MC', 'VA', 'GI', 'BN'];

    private const SUSPECT_CATEGORIES_KR = [
        '전기차충전소', '충전소', '주차장', '자동차정비', '주유소',
        '은행', '병원', '부동산',
    ];

    private const SUSPECT_TYPES_GOOGLE = [
        'electric_vehicle_charging_station', 'parking', 'gas_station',
        'car_repair', 'bank', 'hospital', 'real_estate_agency',
    ];

    public function normalizeSido(string $sido): string
    {
        return self::SIDO_MAP[trim($sido)] ?? trim($sido);
    }

    private const LATIN_MAP = [
        'đ' => 'd', 'Đ' => 'd', 'ø' => 'o', 'Ø' => 'o',
        'ß' => 'ss', 'æ' => 'ae', 'Æ' => 'ae', 'œ' => 'oe', 'Œ' => 'oe',
        'ł' => 'l', 'Ł' => 'l', 'ð' => 'd', 'Ð' => 'd',
        'þ' => 'th', 'Þ' => 'th', 'ı' => 'i',
    ];

    public static function normalizeKey(?string $value): ?string
    {
        if (!$value) return null;

        $v = strtr($value, self::LATIN_MAP);
        $v = \Normalizer::normalize($v, \Normalizer::FORM_D);
        $v = preg_replace('/\p{Mn}/u', '', $v);
        $v = mb_strtolower($v);
        $v = preg_replace('/[^a-z0-9]/', '', $v);

        return $v ?: null;
    }

    public function parseKorean(?string $address, ?array $kakaoResponse = null): array
    {
        $result = [
            'country_code' => 'KR',
            'region_l1' => null, 'region_l2' => null,
            'region_l1_key' => null, 'region_l2_key' => null,
        ];

        if ($kakaoResponse) {
            $l1 = $kakaoResponse['region_1depth_name'] ?? null;
            $l2 = $kakaoResponse['region_2depth_name'] ?? null;
            if ($l1) {
                $result['region_l1'] = $this->normalizeSido($l1);
                $result['region_l2'] = $l2 ?: null;
                $result['region_l1_key'] = $result['region_l1'];
                $result['region_l2_key'] = $result['region_l2'];
                return $result;
            }
        }

        if (!$address) return $result;

        $parts = preg_split('/\s+/', trim($address));
        if (count($parts) < 2) return $result;

        $sido = $this->normalizeSido($parts[0]);
        if (!in_array($sido, array_values(self::SIDO_MAP))) {
            return $result;
        }

        $result['region_l1'] = $sido;
        $result['region_l1_key'] = $sido;

        $sigungu = $parts[1] ?? '';
        if (preg_match('/(시|군|구)$/', $sigungu)) {
            if (isset($parts[2]) && preg_match('/구$/', $parts[2]) && preg_match('/시$/', $sigungu)) {
                $result['region_l2'] = $sigungu . ' ' . $parts[2];
            } else {
                $result['region_l2'] = $sigungu;
            }
            $result['region_l2_key'] = $result['region_l2'];
        }

        return $result;
    }

    public function parseOverseas(?array $addressComponents): array
    {
        $result = [
            'country_code' => null,
            'region_l1' => null, 'region_l2' => null,
            'region_l1_key' => null, 'region_l2_key' => null,
        ];

        if (!$addressComponents || empty($addressComponents)) return $result;

        $countryShort = null;
        $countryLong = null;
        $admin1 = null;
        $locality = null;
        $postalTown = null;
        $admin2 = null;
        $sublocality = null;

        foreach ($addressComponents as $comp) {
            $types = $comp['types'] ?? [];
            if (in_array('country', $types)) {
                $countryShort = $comp['shortText'] ?? ($comp['short_name'] ?? null);
                $countryLong = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
            if (in_array('administrative_area_level_1', $types)) {
                $admin1 = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
            if (in_array('locality', $types)) {
                $locality = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
            if (in_array('postal_town', $types)) {
                $postalTown = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
            if (in_array('administrative_area_level_2', $types)) {
                $admin2 = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
            if (in_array('sublocality', $types) || in_array('sublocality_level_1', $types)) {
                $sublocality = $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
        }

        $result['country_code'] = $countryShort;

        if ($admin1) {
            $result['region_l1'] = $admin1;
        } elseif (in_array($countryShort, self::CITY_STATES) && $countryLong) {
            $result['region_l1'] = $countryLong;
        }

        $result['region_l2'] = $locality ?? $postalTown ?? $admin2 ?? $sublocality;

        return $result;
    }

    public function parseOverseasDual(?array $koComponents, ?array $enComponents): array
    {
        $ko = $this->parseOverseas($koComponents);
        $en = $this->parseOverseas($enComponents);

        $cc = $ko['country_code'] ?? $en['country_code'];
        $isCityState = in_array($cc, self::CITY_STATES);

        if ($isCityState) {
            $koCountryName = $this->countryCodeToName($cc);
            $enCountryName = $en['country_code'] ? ($this->extractCountryLong($enComponents) ?? $cc) : $cc;
            return [
                'country_code' => $cc,
                'region_l1' => $koCountryName,
                'region_l2' => null,
                'region_l1_key' => self::normalizeKey($enCountryName),
                'region_l2_key' => null,
            ];
        }

        if ($ko['region_l1'] && $this->isAsciiOnly($ko['region_l1'])) {
            $koDisplay1 = $en['region_l1'];
        } else {
            $koDisplay1 = $ko['region_l1'];
        }
        if ($ko['region_l2'] && $this->isAsciiOnly($ko['region_l2'])) {
            $koDisplay2 = $en['region_l2'];
        } else {
            $koDisplay2 = $ko['region_l2'];
        }

        return [
            'country_code' => $cc,
            'region_l1' => $koDisplay1 ?? $en['region_l1'],
            'region_l2' => $koDisplay2 ?? $en['region_l2'],
            'region_l1_key' => self::normalizeKey($en['region_l1']),
            'region_l2_key' => self::normalizeKey($en['region_l2']),
        ];
    }

    private function extractCountryLong(?array $components): ?string
    {
        if (!$components) return null;
        foreach ($components as $comp) {
            if (in_array('country', $comp['types'] ?? [])) {
                return $comp['longText'] ?? ($comp['long_name'] ?? null);
            }
        }
        return null;
    }

    public function fetchOverseasRegion(string $googlePlaceId): ?array
    {
        $key = config('services.google_maps.api_key');
        if (!$key) return null;

        $fieldMask = 'addressComponents';

        $koRes = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => $fieldMask,
        ])->timeout(6)->get("https://places.googleapis.com/v1/places/{$googlePlaceId}", [
            'languageCode' => 'ko',
        ]);

        usleep(100_000);

        $enRes = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => $fieldMask,
        ])->timeout(6)->get("https://places.googleapis.com/v1/places/{$googlePlaceId}", [
            'languageCode' => 'en',
        ]);

        $koComponents = $koRes->successful() ? $koRes->json('addressComponents') : null;
        $enComponents = $enRes->successful() ? $enRes->json('addressComponents') : null;

        if (!$koComponents && !$enComponents) return null;

        $region = $this->parseOverseasDual($koComponents, $enComponents);

        $region['address_raw'] = [
            'ko' => $koComponents,
            'en' => $enComponents,
        ];

        return $region;
    }

    public function parse(?string $address, bool $isOverseas, ?array $rawData = null): array
    {
        if ($isOverseas) {
            $result = $this->parseOverseas($rawData);
            $result['region_l1_key'] = self::normalizeKey($result['region_l1']);
            $result['region_l2_key'] = self::normalizeKey($result['region_l2']);
            return $result;
        }
        return $this->parseKorean($address, $rawData);
    }

    public function isSuspectCategory(?string $category, bool $isOverseas = false): bool
    {
        if (!$category) return false;

        if ($isOverseas) {
            $lower = strtolower($category);
            foreach (self::SUSPECT_TYPES_GOOGLE as $type) {
                if (str_contains($lower, $type)) return true;
            }
            return false;
        }

        foreach (self::SUSPECT_CATEGORIES_KR as $suspect) {
            if (str_contains($category, $suspect)) return true;
        }
        return false;
    }

    public function deriveRegionLabels(\Illuminate\Database\Eloquent\Collection $places): array
    {
        $domestic = [];
        $overseas = [];
        $seenKeys = [];

        foreach ($places as $place) {
            if (!$place->country_code) continue;

            $groupKey = ($place->country_code) . '|' . ($place->region_l1_key ?? $place->region_l1) . '|' . ($place->region_l2_key ?? $place->region_l2);
            if (isset($seenKeys[$groupKey])) continue;
            $seenKeys[$groupKey] = true;

            if ($place->country_code === 'KR') {
                $label = $place->region_l1;
                if ($label && $place->region_l2) {
                    $label .= ' ' . $place->region_l2;
                }
                if ($label) $domestic[] = $label;
            } else {
                $countryName = $this->countryCodeToName($place->country_code);
                $label = $countryName;
                if ($place->region_l2) {
                    $label .= ' ' . $place->region_l2;
                } elseif ($place->region_l1) {
                    $label .= ' ' . $place->region_l1;
                }
                if ($label) $overseas[] = $label;
            }
        }

        return array_merge($overseas, $domestic);
    }

    public function buildRegionCodesCache(\Illuminate\Database\Eloquent\Collection $places): array
    {
        $entries = [];
        $seen = [];

        foreach ($places as $place) {
            if (!$place->country_code) continue;

            $key = $place->country_code . '|' . ($place->region_l1_key ?? $place->region_l1) . '|' . ($place->region_l2_key ?? $place->region_l2);

            if (isset($seen[$key])) {
                $seen[$key]['count']++;
                continue;
            }

            $entry = [
                'country_code' => $place->country_code,
                'region_l1' => $place->region_l1,
                'region_l2' => $place->region_l2,
                'region_l1_key' => $place->region_l1_key,
                'region_l2_key' => $place->region_l2_key,
                'count' => 1,
            ];
            $seen[$key] = &$entry;
            $entries[] = &$entry;
            unset($entry);
        }

        return $entries;
    }

    public function countryCodeToName(string $code): string
    {
        $map = [
            'JP' => '일본', 'US' => '미국', 'CN' => '중국', 'TW' => '대만',
            'TH' => '태국', 'VN' => '베트남', 'SG' => '싱가포르', 'MY' => '말레이시아',
            'ID' => '인도네시아', 'PH' => '필리핀', 'AU' => '호주', 'NZ' => '뉴질랜드',
            'GB' => '영국', 'FR' => '프랑스', 'DE' => '독일', 'IT' => '이탈리아',
            'ES' => '스페인', 'PT' => '포르투갈', 'CH' => '스위스', 'AT' => '오스트리아',
            'NL' => '네덜란드', 'BE' => '벨기에', 'CZ' => '체코', 'HU' => '헝가리',
            'PL' => '폴란드', 'GR' => '그리스', 'TR' => '튀르키예', 'HR' => '크로아티아',
            'HK' => '홍콩', 'MO' => '마카오', 'CA' => '캐나다', 'MX' => '멕시코',
            'IN' => '인도', 'KH' => '캄보디아', 'MM' => '미얀마', 'LA' => '라오스',
            'NP' => '네팔', 'MN' => '몽골', 'FI' => '핀란드', 'SE' => '스웨덴',
            'NO' => '노르웨이', 'DK' => '덴마크', 'IS' => '아이슬란드',
            'MC' => '모나코', 'VA' => '바티칸', 'GI' => '지브롤터', 'BN' => '브루나이',
        ];
        return $map[$code] ?? $code;
    }

    public function getSuspectCategoriesKr(): array
    {
        return self::SUSPECT_CATEGORIES_KR;
    }

    public function getSuspectTypesGoogle(): array
    {
        return self::SUSPECT_TYPES_GOOGLE;
    }

    public static function resolveCanonicalDisplay(string $countryCode, ?string $l1Key, ?string $l2Key): ?array
    {
        if (!$l1Key) return null;

        $buildQuery = function (string $table) use ($countryCode, $l1Key, $l2Key) {
            $q = \Illuminate\Support\Facades\DB::table($table)
                ->where('country_code', $countryCode)
                ->where('region_l1_key', $l1Key)
                ->whereNotNull('region_l1');

            if ($l2Key) {
                $q->where('region_l2_key', $l2Key);
                $q->select('region_l1', 'region_l2', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'));
                $q->groupBy('region_l1', 'region_l2');
            } else {
                $q->select('region_l1', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'));
                $q->groupBy('region_l1');
            }

            return $q->orderByDesc('cnt')->first();
        };

        $row = $buildQuery('places') ?? $buildQuery('curation_places');

        if (!$row) return null;

        $result = ['region_l1' => $row->region_l1];
        if ($l2Key) {
            $result['region_l2'] = $row->region_l2;
        }
        return $result;
    }

    public static function applyCanonicalDisplay(array &$region): void
    {
        $cc = $region['country_code'] ?? null;
        $l1Key = $region['region_l1_key'] ?? null;
        $l2Key = $region['region_l2_key'] ?? null;
        if (!$cc || !$l1Key) return;

        $l1Canon = self::resolveCanonicalDisplay($cc, $l1Key, null);
        if ($l1Canon) {
            $region['region_l1'] = $l1Canon['region_l1'];
        }

        if ($l2Key) {
            $l2Canon = self::resolveCanonicalDisplay($cc, $l1Key, $l2Key);
            if ($l2Canon && isset($l2Canon['region_l2'])) {
                $region['region_l2'] = $l2Canon['region_l2'];
            }
        }
    }

    private function isAsciiOnly(string $str): bool
    {
        return mb_strlen($str) === strlen($str);
    }
}
