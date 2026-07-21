<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['list', 'course'])->default('list');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('region_label', 50)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('save_count')->default(0);
            $table->timestamps();
            $table->index('status');
            $table->index('region_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curations');
    }
};
