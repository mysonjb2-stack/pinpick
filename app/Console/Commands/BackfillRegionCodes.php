<?php

namespace App\Console\Commands;

use App\Models\Curation;
use App\Models\CurationPlace;
use App\Services\AddressParserService;
use Illuminate\Console\Command;

class BackfillRegionCodes extends Command
{
    protected $signature = 'region:backfill';
    protected $description = 'Backfill country_code/region_l1/region_l2/keys for curation places';

    public function handle(): int
    {
        $parser = app(AddressParserService::class);

        $domestic = CurationPlace::where('is_overseas', false)
            ->where(fn($q) => $q->whereNull('region_l1_key')->orWhereNull('country_code'))
            ->get();
        $overseas = CurationPlace::where('is_overseas', true)
            ->where(fn($q) => $q->whereNull('region_l1_key')->orWhereNull('country_code'))
            ->get();

        $this->info("=== 백필 대상 ===");
        $this->info("국내: {$domestic->count()}건, 해외: {$overseas->count()}건");

        $domSuccess = 0;
        $domFail = [];

        foreach ($domestic as $place) {
            $region = $parser->parseKorean($place->address);
            if (!$region['region_l1']) {
                $region = $parser->parseKorean($place->jibeon_address);
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
                $domFail[] = "#{$place->id} {$place->place_name} (주소: {$place->address})";
            }
        }

        $ovsSuccess = 0;
        $ovsFail = [];

        foreach ($overseas as $place) {
            if ($place->google_place_id) {
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
                    $ovsSuccess++;
                    continue;
                }
            }
            $ovsFail[] = "#{$place->id} {$place->place_name} (주소: {$place->address})";
        }

        $this->info("\n=== 국내 결과 ===");
        $this->info("성공: {$domSuccess}건");
        if ($domFail) {
            $this->warn("실패: " . count($domFail) . "건");
            foreach ($domFail as $f) $this->line("  - {$f}");
        }

        $this->info("\n=== 해외 결과 ===");
        $this->info("성공: {$ovsSuccess}건");
        if ($ovsFail) {
            $this->warn("실패: " . count($ovsFail) . "건");
            foreach ($ovsFail as $f) $this->line("  - {$f}");
        }

        $this->info("\n=== 큐레이션 region_codes 캐시 갱신 ===");
        $curations = Curation::all();
        foreach ($curations as $curation) {
            $curation->refreshRegionCodes();
            $label = $curation->fresh()->region_label;
            $this->line("  #{$curation->id} {$curation->title} → {$label}");
        }

        $this->info("\n완료!");
        return self::SUCCESS;
    }
}
