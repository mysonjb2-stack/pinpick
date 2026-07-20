<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_places', function (Blueprint $table) {
            $table->string('naver_place_id', 100)->nullable()->after('external_place_id');
            $table->string('google_place_id', 100)->nullable()->after('naver_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('shared_places', function (Blueprint $table) {
            $table->dropColumn(['naver_place_id', 'google_place_id']);
        });
    }
};
