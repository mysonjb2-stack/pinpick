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
    private const MAIN_MAX = 1600;
    private const THUMB_MAX = 600;
    private const MAIN_QUALITY = 82;
    private const THUMB_QUALITY = 78;

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

    public static function thumbPathFor(string $mainPath): string
    {
        $dir = dirname($mainPath);
        $base = pathinfo($mainPath, PATHINFO_FILENAME);
        $prefix = ($dir === '' || $dir === '.') ? '' : $dir . '/';
        return $prefix . 'thumb_' . $base . '.webp';
    }
}
