<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('categories')
            ->select('user_id', 'name', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'name')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $removeIds = DB::table('categories')
                ->where('user_id', $dup->user_id)
                ->where('name', $dup->name)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id');

            if ($removeIds->isNotEmpty()) {
                DB::table('places')
                    ->where('user_id', $dup->user_id)
                    ->whereIn('category_id', $removeIds)
                    ->update(['category_id' => $dup->keep_id]);

                DB::table('categories')
                    ->whereIn('id', $removeIds)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // irreversible
    }
};
