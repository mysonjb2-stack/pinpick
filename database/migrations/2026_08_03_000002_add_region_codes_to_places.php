<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->json('address_raw')->nullable()->after('road_address');
            $table->char('country_code', 2)->nullable()->after('address_raw');
            $table->string('region_l1', 50)->nullable()->after('country_code');
            $table->string('region_l2', 50)->nullable()->after('region_l1');

            $table->index('country_code');
            $table->index(['country_code', 'region_l1']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'region_l1']);
            $table->dropIndex(['country_code']);
            $table->dropColumn(['address_raw', 'country_code', 'region_l1', 'region_l2']);
        });
    }
};
