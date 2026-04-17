<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // 네이버 플레이스 ID는 이미 존재 (varchar 255). 구글 Places ID + 매칭 시각만 추가.
            if (!Schema::hasColumn('places', 'google_place_id')) {
                $table->string('google_place_id', 255)->nullable()->after('kakao_place_id');
            }
            if (!Schema::hasColumn('places', 'naver_matched_at')) {
                $table->timestamp('naver_matched_at')->nullable()->after('google_place_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (Schema::hasColumn('places', 'naver_matched_at')) {
                $table->dropColumn('naver_matched_at');
            }
            if (Schema::hasColumn('places', 'google_place_id')) {
                $table->dropColumn('google_place_id');
            }
        });
    }
};
