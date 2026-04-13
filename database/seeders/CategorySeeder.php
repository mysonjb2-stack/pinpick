<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '자주 가는 곳', 'icon' => '📍'],
            ['name' => '맛집',          'icon' => '🍽'],
            ['name' => '카페',          'icon' => '☕'],
            ['name' => '여행',          'icon' => '✈️'],
            ['name' => '뷰티/케어',     'icon' => '💆'],
            ['name' => '병원/약국',     'icon' => '🏥'],
        ];

        foreach ($categories as $i => $cat) {
            DB::table('categories')->updateOrInsert(
                ['user_id' => null, 'name' => $cat['name']],
                [
                    'icon' => $cat['icon'],
                    'sort_order' => $i + 1,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
