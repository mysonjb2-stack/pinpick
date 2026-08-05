<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona_excluded_nicknames', function (Blueprint $table) {
            $table->id();
            $table->string('nickname', 30)->unique();
            $table->timestamp('excluded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_excluded_nicknames');
    }
};
