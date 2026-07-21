<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curation_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curation_id')->constrained()->cascadeOnDelete();
            $table->string('place_name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('category_label', 50)->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('external_place_id')->nullable();
            $table->boolean('is_overseas')->default(false);
            $table->string('source_channel')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('source_date')->nullable();
            $table->unsignedSmallInteger('day_number')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('editor_note')->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('building_name', 100)->nullable();
            $table->string('naver_place_id')->nullable();
            $table->string('google_place_id', 255)->nullable();
            $table->timestamps();
            $table->index('curation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curation_places');
    }
};
