<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->string('region_l1_key', 80)->nullable()->after('region_l2');
            $table->string('region_l2_key', 80)->nullable()->after('region_l1_key');
            $table->index(['country_code', 'region_l1_key']);
        });

        Schema::table('curation_places', function (Blueprint $table) {
            $table->string('region_l1_key', 80)->nullable()->after('region_l2');
            $table->string('region_l2_key', 80)->nullable()->after('region_l1_key');
            $table->index(['country_code', 'region_l1_key']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'region_l1_key']);
            $table->dropColumn(['region_l1_key', 'region_l2_key']);
        });

        Schema::table('curation_places', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'region_l1_key']);
            $table->dropColumn(['region_l1_key', 'region_l2_key']);
        });
    }
};
