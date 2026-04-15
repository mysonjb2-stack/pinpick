<?php

namespace App\Console\Commands;

use App\Models\PlaceImage;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillPlaceImageThumbs extends Command
{
    protected $signature = 'images:backfill-thumbs {--force : 이미 존재하는 썸네일도 재생성}';
    protected $description = '기존 PlaceImage에 대해 썸네일(thumb_)을 생성';

    public function handle(ImageProcessor $processor): int
    {
        $disk = Storage::disk('public');
        $images = PlaceImage::all();
        $total = $images->count();
        $ok = $skip = $fail = 0;

        $this->info("대상 이미지: {$total}개");
        $bar = $this->output->createProgressBar($total);

        foreach ($images as $img) {
            $thumbPath = ImageProcessor::thumbPathFor($img->path);
            if (!$this->option('force') && $disk->exists($thumbPath)) {
                $skip++;
                $bar->advance();
                continue;
            }
            if (!$disk->exists($img->path)) {
                $fail++;
                $bar->advance();
                continue;
            }
            try {
                $processor->generateThumbFrom($img->path);
                $ok++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("실패 [{$img->id}] {$img->path}: {$e->getMessage()}");
                $fail++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("생성: {$ok}  /  건너뜀: {$skip}  /  실패: {$fail}");
        return self::SUCCESS;
    }
}
