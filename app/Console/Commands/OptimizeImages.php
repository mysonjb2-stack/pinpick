<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\SharedPlace;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--dry-run : 실제 변환 없이 대상만 표시}';
    protected $description = '비-WebP 이미지를 WebP로 일괄 변환 (place_images, shared, static-maps)';

    public function handle(ImageProcessor $processor): int
    {
        $dry = $this->option('dry-run');
        $disk = Storage::disk('public');

        // 1) place_images 변환
        $this->info('=== Place Images ===');
        $images = PlaceImage::all();
        $converted = $skipped = $failed = 0;

        foreach ($images as $img) {
            $ext = strtolower(pathinfo($img->path, PATHINFO_EXTENSION));
            if ($ext === 'webp') { $skipped++; continue; }

            if ($dry) {
                $this->line("  [변환 대상] {$img->path}");
                $converted++;
                continue;
            }

            $newPath = $processor->convertToWebp($img->path);
            if ($newPath) {
                $oldThumb = ImageProcessor::thumbPathFor($img->path);
                $img->update(['path' => $newPath]);
                $processor->generateThumbFrom($newPath);
                if ($disk->exists($oldThumb)) $disk->delete($oldThumb);
                $converted++;
            } else {
                $this->warn("  실패: {$img->path}");
                $failed++;
            }
        }
        $this->info("  변환: {$converted} / 건너뜀: {$skipped} / 실패: {$failed}");

        // 2) shared places 이미지 변환
        $this->info('=== Shared Places ===');
        $sharedPlaces = SharedPlace::whereNotNull('thumbnail_url')->get();
        $converted = $skipped = $failed = 0;

        foreach ($sharedPlaces as $sp) {
            $parsed = parse_url($sp->thumbnail_url, PHP_URL_PATH);
            $storagePath = str_replace('/storage/', '', $parsed);

            $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
            if ($ext === 'webp') { $skipped++; continue; }

            if (!$disk->exists($storagePath)) { $failed++; continue; }

            if ($dry) {
                $this->line("  [변환 대상] {$storagePath}");
                $converted++;
                continue;
            }

            $newPath = $processor->convertToWebp($storagePath, 800, 75);
            if ($newPath) {
                $sp->update(['thumbnail_url' => asset('storage/' . $newPath)]);
                $converted++;
            } else {
                $this->warn("  실패: {$storagePath}");
                $failed++;
            }
        }
        $this->info("  변환: {$converted} / 건너뜀: {$skipped} / 실패: {$failed}");

        // 3) static-map 썸네일 변환
        $this->info('=== Static Map Thumbnails ===');
        $places = Place::whereNotNull('thumbnail')
            ->where('thumbnail', 'like', '%.jpg')
            ->get(['id', 'thumbnail']);
        $converted = $skipped = $failed = 0;

        foreach ($places as $place) {
            if (!$disk->exists($place->thumbnail)) { $failed++; continue; }

            if ($dry) {
                $this->line("  [변환 대상] {$place->thumbnail}");
                $converted++;
                continue;
            }

            $newPath = $processor->convertToWebp($place->thumbnail, 600, 72);
            if ($newPath) {
                $place->thumbnail = $newPath;
                $place->saveQuietly();
                $converted++;
            } else {
                $this->warn("  실패: {$place->thumbnail}");
                $failed++;
            }
        }
        $this->info("  변환: {$converted} / 건너뜀: {$skipped} / 실패: {$failed}");

        if ($dry) {
            $this->newLine();
            $this->warn('--dry-run 모드입니다. 실제 변환하려면 플래그를 제거하세요.');
        }

        return self::SUCCESS;
    }
}
