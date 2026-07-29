<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_place_cache', function (Blueprint $table) {
            $table->id();
            $table->string('google_place_id', 255)->unique();
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('google_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('google_place_id', 255)->index();
            $table->string('author_name', 150)->nullable();
            $table->string('profile_photo_url', 500)->nullable();
            $table->decimal('rating', 2, 0)->default(0);
            $table->text('text')->nullable();
            $table->timestamp('review_time')->nullable()->index();
            $table->timestamps();

            $table->unique(['google_place_id', 'author_name', 'review_time'], 'gr_dedup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_reviews');
        Schema::dropIfExists('google_place_cache');
    }
};
