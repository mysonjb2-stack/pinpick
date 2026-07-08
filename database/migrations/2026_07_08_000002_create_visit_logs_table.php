<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip', 45);
            $table->string('path', 500);
            $table->string('user_agent', 500)->nullable();
            $table->date('visited_date')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['visited_date', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_logs');
    }
};
