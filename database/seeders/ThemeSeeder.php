<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['name' => '음식',       'slug' => 'food',     'sort_order' => 1],
            ['name' => '카페',       'slug' => 'cafe',     'sort_order' => 2],
            ['name' => '여행',       'slug' => 'travel',   'sort_order' => 3],
            ['name' => '뷰티/케어',  'slug' => 'beauty',   'sort_order' => 4],
            ['name' => '병원/약국',  'slug' => 'medical',  'sort_order' => 5],
            ['name' => '숙소',       'slug' => 'stay',     'sort_order' => 6],
            ['name' => '쇼핑',       'slug' => 'shopping', 'sort_order' => 7],
            ['name' => '문화/여가',  'slug' => 'culture',  'sort_order' => 8],
            ['name' => '기타',       'slug' => 'etc',      'sort_order' => 9],
        ];

        foreach ($themes as $t) {
            DB::table('themes')->updateOrInsert(
                ['slug' => $t['slug']],
                [
                    'name' => $t['name'],
                    'sort_order' => $t['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
