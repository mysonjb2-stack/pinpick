<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('transit_hint');
        });
    }

    public function down(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->dropColumn('is_manual');
        });
    }
};
