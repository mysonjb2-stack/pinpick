<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_reports', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('detail');
            $table->string('result', 20)->nullable()->after('status');
            $table->timestamp('handled_at')->nullable()->after('result');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('is_review_account');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
            $table->string('suspend_reason', 255)->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('curation_reports', function (Blueprint $table) {
            $table->dropColumn(['status', 'result', 'handled_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_suspended', 'suspended_at', 'suspend_reason']);
        });
    }
};
