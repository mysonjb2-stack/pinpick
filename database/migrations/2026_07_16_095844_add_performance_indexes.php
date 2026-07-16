<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index(['user_id', 'is_visible', 'created_at']);
        });

        Schema::table('place_images', function (Blueprint $table) {
            $table->index(['place_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['user_id', 'is_visible', 'created_at']);
        });

        Schema::table('place_images', function (Blueprint $table) {
            $table->dropIndex(['place_id', 'sort_order']);
        });
    }
};
