<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_collection_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('original_place_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('category_label')->nullable();
            $table->string('memo', 500)->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('external_place_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->index('shared_collection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_places');
    }
};
