<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['trip_id', 'day_number', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_places');
    }
};
