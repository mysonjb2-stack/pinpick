<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_places', function (Blueprint $table) {
            $table->boolean('is_overseas')->default(false)->after('external_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('shared_places', function (Blueprint $table) {
            $table->dropColumn('is_overseas');
        });
    }
};
