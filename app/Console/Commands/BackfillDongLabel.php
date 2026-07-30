<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BackfillDongLabel extends Command
{
    protected $signature = 'curation:backfill-dong';
    protected $description = 'Backfill dong_label for curation_places using jibeon address or reverse geocoding';

    public function handle(): int
    {
        $places = DB::table('curation_places')
            ->whereNull('dong_label')
            ->where('is_overseas', 0)
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->get(['id', 'jibeon_address', 'latitude', 'longitude']);

        $this->info("Processing {$places->count()} places...");
        $apiKey = config('services.kakao_local.rest_api_key');
        $updated = 0;
        $failed = 0;

        foreach ($places as $p) {
            $label = null;

            if ($p->jibeon_address) {
                $label = $this->parseDongFromJibeon($p->jibeon_address);
            }

            if (!$label && $apiKey) {
                $label = $this->reverseGeocode($p->latitude, $p->longitude, $apiKey);
                if ($label) usleep(100000);
            }

            if ($label) {
                DB::table('curation_places')->where('id', $p->id)->update(['dong_label' => $label]);
                $updated++;
                $this->line("  #{$p->id} → {$label}");
            } else {
                $failed++;
                $this->warn("  #{$p->id} — no dong found");
            }
        }

        $this->info("Done. Updated: {$updated}, Failed: {$failed}");
        return 0;
    }

    private function parseDongFromJibeon(string $address): ?string
    {
        $parts = preg_split('/\s+/', trim($address));
        foreach ($parts as $part) {
            if (preg_match('/(동|읍|면)$/', $part)) {
                return $this->cleanDong($part);
            }
        }
        return null;
    }

    private function reverseGeocode(float $lat, float $lng, string $apiKey): ?string
    {
        try {
            $res = Http::withHeaders(['Authorization' => 'KakaoAK ' . $apiKey])
                ->timeout(5)
                ->get('https://dapi.kakao.com/v2/local/geo/coord2regioncode.json', [
                    'x' => $lng, 'y' => $lat,
                ]);

            $doc = collect($res->json('documents', []))->firstWhere('region_type', 'H');
            if (!$doc) return null;

            $parts = explode(' ', $doc['address_name']);
            $dong = end($parts);
            if (!preg_match('/(동|읍|면)$/', $dong)) return null;

            return $this->cleanDong($dong);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cleanDong(string $dong): string
    {
        $dong = preg_replace('/\d+(동)$/', '$1', $dong);
        $name = preg_replace('/(동|읍|면)$/', '', $dong);
        if ($name === '' || mb_strlen($name) < 2) return $dong;
        return $name;
    }
}
