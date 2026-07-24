<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageProcessor
{
    private const MAIN_MAX = 1200;
    private const THUMB_MAX = 480;
    private const MAIN_QUALITY = 78;
    private const THUMB_QUALITY = 72;

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
            $image = $this->manager->decodeString($binary);
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
}
