<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_operator_persona')->default(false)->after('remember_token')->index();
            $table->string('bio', 100)->nullable()->after('is_operator_persona');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_operator_persona']);
            $table->dropColumn(['is_operator_persona', 'bio']);
        });
    }
};
