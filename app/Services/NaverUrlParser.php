<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 네이버 장소 URL에서 place_id를 추출한다.
 *
 * 지원 포맷:
 *  - 숫자만 (예: "20848021")
 *  - https://m.place.naver.com/place/20848021...
 *  - https://map.naver.com/p/entry/place/20848021
 *  - https://map.naver.com/v5/entry/place/20848021
 *  - https://pcmap.place.naver.com/restaurant/20848021/home
 *  - https://naver.me/XXXXXXXX  (단축 URL → Location 헤더 추적 후 재파싱)
 */
class NaverUrlParser
{
    public function extractPlaceId(?string $input): ?string
    {
        if (!$input) return null;
        $s = trim($input);
        if ($s === '') return null;

        // 1) 숫자만 (place_id 직접 입력)
        if (preg_match('/^\d{3,20}$/', $s)) {
            return $s;
        }

        // 2) URL 본문에서 /place/숫자, /entry/place/숫자, /restaurant/숫자 등 범용 매칭
        if (preg_match('#/(?:place|entry/place|restaurant|cafe|hairshop|beautysalon|accommodation|hospital|attraction)/(\d{3,20})#i', $s, $m)) {
            return $m[1];
        }

        // 3) naver.me 단축 URL → 실제 URL로 리다이렉트 추적
        if (preg_match('#^https?://naver\.me/[A-Za-z0-9]+#i', $s)) {
            $resolved = $this->resolveShortUrl($s);
            if ($resolved && $resolved !== $s) {
                // 리다이렉트된 URL을 다시 파싱 (재귀 1회로 제한됨 — naver.me는 바로 m.place로 가므로)
                return $this->extractPlaceId($resolved);
            }
        }

        // 4) 쿼리스트링에 id=숫자 형태가 있는 경우 (예전 포맷)
        if (preg_match('/[?&]id=(\d{3,20})/', $s, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * naver.me 단축 URL에서 Location 헤더를 따라가 최종 URL을 얻는다.
     * cURL이 없거나 실패 시 null 반환.
     */
    private function resolveShortUrl(string $url): ?string
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PinpickBot/1.0)',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resp === false || $code < 300 || $code >= 400) return null;

            if (preg_match('/^location:\s*(\S+)/mi', $resp, $m)) {
                return trim($m[1]);
            }
        } catch (\Throwable $e) {
            Log::warning('naver short url resolve failed: ' . $e->getMessage(), ['url' => $url]);
        }
        return null;
    }
}
