<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Services\AddressParserService;
use Illuminate\Console\Command;

class BackfillPlaceRegionCodes extends Command
{
    protected $signature = 'region:backfill-places {--dry-run : 사전 보고만} {--overseas-only : 해외만 재처리}';
    protected $description = 'Backfill region codes for personal places (dual API for overseas)';

    public function handle(): int
    {
        $parser = app(AddressParserService::class);

        $domestic = Place::whereNull('deleted_at')->where('is_overseas', false)
            ->where(fn($q) => $q->whereNull('region_l1_key')->orWhereNull('country_code'))
            ->get();

        $overseas = Place::whereNull('deleted_at')->where('is_overseas', true)->get();

        $ovsWithGoogle = $overseas->filter(fn($p) => $p->google_place_id);
        $ovsNoGoogle = $overseas->filter(fn($p) => !$p->google_place_id);

        $this->info("=== 백필 대상 ===");
        if (!$this->option('overseas-only')) {
            $this->info("국내: {$domestic->count()}건");
        }
        $this->info("해외 google_place_id 있음: {$ovsWithGoogle->count()}건 (API 듀얼 재조회)");
        $this->info("해외 google_place_id 없음: {$ovsNoGoogle->count()}건 (수동 보정 목록)");

        if ($ovsNoGoogle->isNotEmpty()) {
            $this->warn("\n수동 보정 필요:");
            foreach ($ovsNoGoogle as $p) {
                $this->line("  #{$p->id} {$p->name} (주소: {$p->address})");
            }
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (!$this->option('overseas-only')) {
            $domSuccess = 0;
            $domFail = [];
            foreach ($domestic as $place) {
                $region = $parser->parseKorean($place->road_address ?: $place->address);
                if (!$region['region_l1']) {
                    $region = $parser->parseKorean($place->address);
                }
                if ($region['region_l1']) {
                    $place->update([
                        'country_code' => 'KR',
                        'region_l1' => $region['region_l1'],
                        'region_l2' => $region['region_l2'],
                        'region_l1_key' => $region['region_l1_key'],
                        'region_l2_key' => $region['region_l2_key'],
                    ]);
                    $domSuccess++;
                } else {
                    $domFail[] = "#{$place->id} {$place->name} (주소: {$place->address})";
                }
            }

            $this->info("\n=== 국내 결과 ===");
            $this->info("성공: {$domSuccess}건, 실패: " . count($domFail) . "건");
            foreach ($domFail as $f) $this->line("  - {$f}");
        }

        $apiSuccess = 0;
        $apiFail = [];

        foreach ($ovsWithGoogle as $place) {
            try {
                $region = $parser->fetchOverseasRegion($place->google_place_id);
                if ($region && $region['country_code']) {
                    $place->update([
                        'address_raw' => $region['address_raw'],
                        'country_code' => $region['country_code'],
                        'region_l1' => $region['region_l1'],
                        'region_l2' => $region['region_l2'],
                        'region_l1_key' => $region['region_l1_key'],
                        'region_l2_key' => $region['region_l2_key'],
                    ]);
                    $apiSuccess++;
                    $this->line("  OK #{$place->id} {$place->name} → {$region['country_code']} {$region['region_l1']} ({$region['region_l1_key']})");
                } else {
                    $apiFail[] = "#{$place->id} {$place->name} (파싱 실패)";
                }
                usleep(200_000);
            } catch (\Throwable $e) {
                $apiFail[] = "#{$place->id} {$place->name} ({$e->getMessage()})";
            }
        }

        $this->info("\n=== 해외 결과 ===");
        $this->info("API 듀얼 재조회 성공: {$apiSuccess}건, 실패: " . count($apiFail) . "건");
        foreach ($apiFail as $f) $this->line("  - {$f}");

        $this->info("\n완료!");
        return self::SUCCESS;
    }
}
