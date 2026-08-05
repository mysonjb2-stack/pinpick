<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('persona_scope', 10)->nullable()->after('bio');
            $table->string('persona_region_tag', 20)->nullable()->after('persona_scope');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['persona_scope', 'persona_region_tag']);
        });
    }
};
