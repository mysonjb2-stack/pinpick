<?php

namespace App\Console\Commands;

use App\Models\CurationPlace;
use App\Models\GooglePlaceCache;
use App\Services\GoogleReviewService;
use Illuminate\Console\Command;

class FetchGoogleReviews extends Command
{
    protected $signature = 'google:reviews {place_id? : 특정 Google Place ID만 갱신}';
    protected $description = 'Google Places API로 리뷰/별점/리뷰수 캐싱';

    public function handle(): void
    {
        $service = app(GoogleReviewService::class);
        $specific = $this->argument('place_id');

        if ($specific) {
            $this->info("Fetching: {$specific}");
            $result = $service->fetchAndCache($specific);
            $this->info($result
                ? "Done: rating={$result->rating}, reviews={$result->review_count}"
                : 'Failed or no data');
            return;
        }

        $placeIds = CurationPlace::whereNotNull('google_place_id')
            ->where('google_place_id', '!=', '')
            ->distinct()
            ->pluck('google_place_id')
            ->toArray();

        $this->info('Found ' . count($placeIds) . ' unique Google Place IDs');

        $success = 0;
        $failed = 0;

        foreach ($placeIds as $i => $pid) {
            $pid = trim($pid);
            if (!$pid) continue;

            $cache = GooglePlaceCache::where('google_place_id', $pid)->first();
            if ($cache && $cache->fetched_at && $cache->fetched_at->diffInDays(now()) < 7) {
                continue;
            }

            $result = $service->fetchAndCache($pid);
            if ($result) {
                $success++;
                $this->line(($i + 1) . ": {$pid} → rating={$result->rating}, cnt={$result->review_count}");
            } else {
                $failed++;
                $this->warn(($i + 1) . ": {$pid} → failed");
            }

            usleep(200000);
        }

        $this->info("Complete: {$success} success, {$failed} failed");
    }
}
