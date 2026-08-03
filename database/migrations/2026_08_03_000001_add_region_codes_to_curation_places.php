<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->json('address_raw')->nullable()->after('address');
            $table->char('country_code', 2)->nullable()->after('is_overseas');
            $table->string('region_l1', 50)->nullable()->after('country_code');
            $table->string('region_l2', 50)->nullable()->after('region_l1');

            $table->index('country_code');
            $table->index('region_l1');
            $table->index('region_l2');
        });

        Schema::table('curations', function (Blueprint $table) {
            $table->json('region_codes')->nullable()->after('region_label');
        });
    }

    public function down(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropIndex(['region_l1']);
            $table->dropIndex(['region_l2']);
            $table->dropColumn(['address_raw', 'country_code', 'region_l1', 'region_l2']);
        });

        Schema::table('curations', function (Blueprint $table) {
            $table->dropColumn('region_codes');
        });
    }
};
