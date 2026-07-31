<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->string('transit_hint', 100)->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->dropColumn('transit_hint');
        });
    }
};
