<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageProcessor
{
    private const MAIN_MAX = 1200;
    private const THUMB_MAX = 480;
    private const MAIN_QUALITY = 78;
    private const THUMB_QUALITY = 72;

    public const OG_WIDTH = 1200;
    public const OG_HEIGHT = 630;
    private const OG_QUALITY = 80;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * 업로드 이미지를 1600px WebP + 600px WebP 썸네일로 저장.
     * @return string 원본 상대 경로 (예: places/123/abc.webp)
     */
    public function processPlaceImage(UploadedFile $file, string $dir): string
    {
        $disk = Storage::disk('public');
        $filename = Str::random(40) . '.webp';
        $mainPath = "$dir/$filename";
        $thumbPath = self::thumbPathFor($mainPath);

        $image = $this->manager->decodePath($file->getRealPath());
        $image->orient();

        $main = clone $image;
        $main->scaleDown(self::MAIN_MAX, self::MAIN_MAX);
        $disk->put($mainPath, (string) $main->encode(new WebpEncoder(self::MAIN_QUALITY)));

        $thumb = clone $image;
        $thumb->scaleDown(self::THUMB_MAX, self::THUMB_MAX);
        $disk->put($thumbPath, (string) $thumb->encode(new WebpEncoder(self::THUMB_QUALITY)));

        $og = clone $image;
        $og->cover(self::OG_WIDTH, self::OG_HEIGHT);
        $disk->put(self::ogPathFor($mainPath), (string) $og->encode(new JpegEncoder(self::OG_QUALITY)));

        return $mainPath;
    }

    /**
     * 기존 파일 경로에서 썸네일만 생성 (backfill용).
     */
    public function generateThumbFrom(string $mainPath): bool
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($mainPath)) return false;

        $image = $this->manager->decodePath($disk->path($mainPath));
        $image->orient();
        $image->scaleDown(self::THUMB_MAX, self::THUMB_MAX);

        $disk->put(self::thumbPathFor($mainPath), (string) $image->encode(new WebpEncoder(self::THUMB_QUALITY)));
        return true;
    }

    /**
     * 기존 이미지 파일을 WebP로 변환 (원본 삭제).
     * @return string|null 새 WebP 경로 (실패 시 null)
     */
    public function convertToWebp(string $storagePath, int $maxWidth = self::MAIN_MAX, int $quality = self::MAIN_QUALITY): ?string
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($storagePath)) return null;

        $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        if ($ext === 'webp') return $storagePath;

        try {
            $image = $this->manager->decodePath($disk->path($storagePath));
            $image->orient();
            $image->scaleDown($maxWidth, $maxWidth);

            $newPath = preg_replace('/\.[^.]+$/', '.webp', $storagePath);
            $disk->put($newPath, (string) $image->encode(new WebpEncoder($quality)));

            if ($newPath !== $storagePath) {
                $disk->delete($storagePath);
            }
            return $newPath;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 바이너리 데이터를 WebP로 변환하여 저장.
     */
    public function saveAsWebp(string $binary, string $storagePath, int $maxWidth = self::MAIN_MAX, int $quality = self::MAIN_QUALITY): bool
    {
        try {
            $image = $this->manager->decodeBinary($binary);
            $image->scaleDown($maxWidth, $maxWidth);

            $webpPath = preg_replace('/\.[^.]+$/', '.webp', $storagePath);
            Storage::disk('public')->put($webpPath, (string) $image->encode(new WebpEncoder($quality)));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function thumbPathFor(string $mainPath): string
    {
        $dir = dirname($mainPath);
        $base = pathinfo($mainPath, PATHINFO_FILENAME);
        $prefix = ($dir === '' || $dir === '.') ? '' : $dir . '/';
        return $prefix . 'thumb_' . $base . '.webp';
    }

    public static function ogPathFor(string $mainPath): string
    {
        $dir = dirname($mainPath);
        $base = pathinfo($mainPath, PATHINFO_FILENAME);
        $prefix = ($dir === '' || $dir === '.') ? '' : $dir . '/';
        return $prefix . 'og_' . $base . '.jpg';
    }

    public function generateOgImage(string $sourcePath): ?string
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($sourcePath)) return null;

        try {
            $image = $this->manager->decodePath($disk->path($sourcePath));
            $image->orient();
            $image->cover(self::OG_WIDTH, self::OG_HEIGHT);

            $ogPath = self::ogPathFor($sourcePath);
            $disk->put($ogPath, (string) $image->encode(new JpegEncoder(self::OG_QUALITY)));
            return $ogPath;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 최대 4장의 이미지를 2x2 모자이크로 합성하여 저장.
     * @param string[] $imagePaths public disk 상대경로
     * @return string|null 합성된 커버 이미지 상대경로
     */
    public function createMosaic(array $imagePaths, string $outputDir): ?string
    {
        $disk = Storage::disk('public');
        $valid = [];
        foreach ($imagePaths as $p) {
            $clean = preg_replace('#^.*/storage/#', '', $p);
            if ($disk->exists($clean)) $valid[] = $clean;
            elseif ($disk->exists($p)) $valid[] = $p;
            if (count($valid) >= 4) break;
        }
        if (empty($valid)) return null;

        $size = 600;
        $positions = [[0, 0], [$size, 0], [0, $size], [$size, $size]];

        if (count($valid) === 1) {
            $canvas = $this->manager->decodePath($disk->path($valid[0]));
            $canvas->cover($size * 2, $size * 2);
        } elseif (count($valid) === 2) {
            $canvas = $this->manager->createImage($size * 2, $size)->fill('ffffff');
            foreach ($valid as $i => $path) {
                $tile = $this->manager->decodePath($disk->path($path));
                $tile->cover($size, $size);
                $canvas->insert($tile, $i * $size, 0);
            }
        } else {
            $canvas = $this->manager->createImage($size * 2, $size * 2)->fill('ffffff');
            foreach ($valid as $i => $path) {
                $tile = $this->manager->decodePath($disk->path($path));
                $tile->cover($size, $size);
                $canvas->insert($tile, $positions[$i][0], $positions[$i][1]);
            }
        }

        $filename = 'mosaic_' . Str::random(20) . '.webp';
        $outPath = "$outputDir/$filename";
        $disk->put($outPath, (string) $canvas->encode(new WebpEncoder(self::MAIN_QUALITY)));
        return $outPath;
    }

    /**
     * 좌표 배열로 정적 지도 OG 이미지(1200x630 JPEG)를 생성.
     * @param array $coords [['lat'=>float,'lng'=>float], ...]
     * @param string $outputDir storage/public 하위 경로
     * @param string $hash 파일명에 포함할 해시 (캐시 무효화용)
     * @return string|null 생성된 파일의 상대 경로
     */
    public function generateMapOgImage(array $coords, string $outputDir, string $hash): ?string
    {
        $valid = array_filter($coords, fn($c) => !empty($c['lat']) && !empty($c['lng']));
        if (empty($valid)) return null;

        $overseas = !empty($valid[0]['is_overseas']);
        $body = $overseas
            ? $this->fetchGoogleMultiMarkerMap($valid)
            : $this->fetchNaverMultiMarkerMap($valid);

        if (!$body) return null;

        try {
            $disk = Storage::disk('public');
            $disk->makeDirectory($outputDir);
            $filename = "og_map_{$hash}.jpg";
            $outPath = "{$outputDir}/{$filename}";

            $image = $this->manager->decodeBinary($body);
            $image->resizeDown(self::OG_WIDTH, self::OG_HEIGHT);
            $disk->put($outPath, (string) $image->encode(new JpegEncoder(self::OG_QUALITY)));
            return $outPath;
        } catch (\Throwable $e) {
            Log::warning('[og-map] encode fail: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchNaverMultiMarkerMap(array $coords): ?string
    {
        $cid = config('services.naver_map.client_id');
        $sec = config('services.naver_map.client_secret');
        if (!$cid || !$sec) return null;

        $w = min(self::OG_WIDTH, 1024);
        $h = min(self::OG_HEIGHT, 1024);
        $zoom = $this->calcFitZoom($coords, $w, $h, 80);
        $center = $this->calcCenter($coords);

        $baseUrl = 'https://maps.apigw.ntruss.com/map-static/v2/raster?' . http_build_query([
            'w' => $w,
            'h' => $h,
            'level' => $zoom,
            'center' => sprintf('%.5f,%.5f', $center['lng'], $center['lat']),
            'lang' => 'ko',
            'format' => 'jpeg',
        ]);

        foreach ($coords as $c) {
            $baseUrl .= '&markers=' . urlencode(
                sprintf('type:d|size:mid|color:0xFFA51A|pos:%.5f %.5f', $c['lng'], $c['lat'])
            );
        }

        $resp = Http::withHeaders([
            'X-NCP-APIGW-API-KEY-ID' => $cid,
            'X-NCP-APIGW-API-KEY' => $sec,
        ])->timeout(8)->get($baseUrl);

        if (!$resp->successful()) {
            Log::warning('[og-map] naver ' . $resp->status() . ' ' . substr($resp->body(), 0, 200));
            return null;
        }
        return $resp->body();
    }

    private function fetchGoogleMultiMarkerMap(array $coords): ?string
    {
        $key = config('services.google_maps.api_key');
        if (!$key) return null;

        $markers = 'color:0xFFA51A|' . collect($coords)->map(
            fn($c) => sprintf('%.5f,%.5f', $c['lat'], $c['lng'])
        )->implode('|');

        $resp = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/staticmap', [
            'size' => self::OG_WIDTH . 'x' . self::OG_HEIGHT,
            'markers' => $markers,
            'language' => 'ko',
            'key' => $key,
        ]);

        if (!$resp->successful()) {
            Log::warning('[og-map] google ' . $resp->status() . ' ' . substr($resp->body(), 0, 200));
            return null;
        }
        return $resp->body();
    }

    private function calcCenter(array $coords): array
    {
        $lats = array_column($coords, 'lat');
        $lngs = array_column($coords, 'lng');
        return [
            'lat' => (min($lats) + max($lats)) / 2,
            'lng' => (min($lngs) + max($lngs)) / 2,
        ];
    }

    private function calcFitZoom(array $coords, int $w, int $h, int $pad = 80): int
    {
        if (count($coords) === 1) return 15;

        $lats = array_column($coords, 'lat');
        $lngs = array_column($coords, 'lng');
        $minLat = min($lats); $maxLat = max($lats);
        $minLng = min($lngs); $maxLng = max($lngs);

        $effW = max($w - $pad, 50);
        $effH = max($h - $pad, 50);
        $lngSpan = $maxLng - $minLng;
        $mercMax = log(tan(M_PI / 4 + deg2rad($maxLat) / 2));
        $mercMin = log(tan(M_PI / 4 + deg2rad($minLat) / 2));
        $mercSpan = abs($mercMax - $mercMin);

        $z = 18;
        if ($lngSpan > 0) $z = min($z, log($effW * 360 / ($lngSpan * 256)) / log(2));
        if ($mercSpan > 0) $z = min($z, log($effH * 2 * M_PI / (256 * $mercSpan)) / log(2));

        // 마커 아이콘 크기 + API 뷰포트 차이 보정
        return max(2, min((int) floor($z) - 1, 16));
    }
}
