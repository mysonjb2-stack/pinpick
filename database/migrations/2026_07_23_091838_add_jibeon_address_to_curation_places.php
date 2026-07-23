<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->string('jibeon_address', 255)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('curation_places', function (Blueprint $table) {
            $table->dropColumn('jibeon_address');
        });
    }
};
