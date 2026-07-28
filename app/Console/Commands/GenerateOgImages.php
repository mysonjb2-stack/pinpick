<?php

namespace App\Console\Commands;

use App\Models\CurationPlace;
use App\Models\SharedPlace;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateOgImages extends Command
{
    protected $signature = 'pinpick:generate-og-images {--dry-run : 실제 생성 없이 대상만 표시}';
    protected $description = '큐레이션/공유 장소의 OG 이미지(1200x630 JPEG)를 일괄 생성';

    public function handle(ImageProcessor $processor): int
    {
        $dry = $this->option('dry-run');
        $disk = Storage::disk('public');
        $created = $skipped = $failed = 0;

        $this->info('=== Curation Place Photos ===');
        CurationPlace::whereNotNull('photos')
            ->chunkById(100, function ($places) use ($processor, $disk, $dry, &$created, &$skipped, &$failed) {
                foreach ($places as $place) {
                    $photos = $place->photos;
                    if (empty($photos)) continue;

                    $firstPhoto = $photos[0];
                    $ogPath = ImageProcessor::ogPathFor($firstPhoto);

                    if ($disk->exists($ogPath)) {
                        $skipped++;
                        continue;
                    }

                    if (!$disk->exists($firstPhoto)) {
                        $failed++;
                        $this->line("  MISS: {$firstPhoto}");
                        continue;
                    }

                    if ($dry) {
                        $this->line("  WOULD: {$firstPhoto} → {$ogPath}");
                        $created++;
                        continue;
                    }

                    $result = $processor->generateOgImage($firstPhoto);
                    if ($result) {
                        $created++;
                    } else {
                        $failed++;
                        $this->line("  FAIL: {$firstPhoto}");
                    }
                }
            });

        $this->info("  Created: {$created} | Skipped: {$skipped} | Failed: {$failed}");

        $created2 = $skipped2 = $failed2 = 0;

        $this->info('=== Shared Collection Thumbnails ===');
        SharedPlace::whereNotNull('thumbnail_url')
            ->where('sort_order', 0)
            ->chunkById(100, function ($places) use ($processor, $disk, $dry, &$created2, &$skipped2, &$failed2) {
                foreach ($places as $sp) {
                    $parsed = parse_url($sp->thumbnail_url, PHP_URL_PATH);
                    if (!$parsed) { $failed2++; continue; }

                    $storagePath = str_replace('/storage/', '', $parsed);
                    $ogPath = ImageProcessor::ogPathFor($storagePath);

                    if ($disk->exists($ogPath)) {
                        $skipped2++;
                        continue;
                    }

                    if (!$disk->exists($storagePath)) {
                        $failed2++;
                        $this->line("  MISS: {$storagePath}");
                        continue;
                    }

                    if ($dry) {
                        $this->line("  WOULD: {$storagePath} → {$ogPath}");
                        $created2++;
                        continue;
                    }

                    $result = $processor->generateOgImage($storagePath);
                    if ($result) {
                        $created2++;
                    } else {
                        $failed2++;
                        $this->line("  FAIL: {$storagePath}");
                    }
                }
            });

        $this->info("  Created: {$created2} | Skipped: {$skipped2} | Failed: {$failed2}");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
