<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Services\NaverPlaceMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatchNaverPlaces extends Command
{
    protected $signature = 'pinpick:match-naver-places
        {--force : naver_matched_at이 이미 기록된 장소도 재시도}
        {--limit=0 : 처리할 최대 건수 (0=제한없음)}';

    protected $description = '네이버 로컬 검색 API로 국내 장소의 naver_place_id를 일괄 매칭';

    public function handle(NaverPlaceMatcher $matcher): int
    {
        $query = Place::whereNull('naver_place_id')
            ->where(function ($q) {
                $q->whereNull('is_overseas')->orWhere('is_overseas', false);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('lat', [33.0, 39.0])
            ->whereBetween('lng', [124.0, 132.0]);

        if (!$this->option('force')) {
            $query->whereNull('naver_matched_at');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $places = $query->get();
        $total = $places->count();

        if ($total === 0) {
            $this->info('매칭 대상 장소가 없습니다.');
            return self::SUCCESS;
        }

        $this->info("대상 장소: {$total}개");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $matched = 0;
        $failed = 0;

        foreach ($places as $place) {
            try {
                $placeId = $matcher->match(
                    $place->name,
                    (float) $place->lat,
                    (float) $place->lng,
                    $place->road_address ?: $place->address
                );

                $place->forceFill([
                    'naver_place_id' => $placeId ?: null,
                    'naver_matched_at' => now(),
                ])->saveQuietly();

                if ($placeId) {
                    $matched++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('match-naver-places item failed: ' . $e->getMessage(), [
                    'place_id' => $place->id,
                ]);
            }

            $bar->advance();
            // 초당 1건 throttle
            usleep(1_000_000);
        }

        $bar->finish();
        $this->newLine(2);

        $rate = $total > 0 ? round($matched / $total * 100, 1) : 0;
        $this->info("처리: {$total}  매칭 성공: {$matched}  실패: {$failed}  매칭률: {$rate}%");

        return self::SUCCESS;
    }
}
