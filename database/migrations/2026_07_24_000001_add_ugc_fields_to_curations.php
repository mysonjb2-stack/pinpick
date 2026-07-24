<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) curations 테이블에 UGC 컬럼 추가
        Schema::table('curations', function (Blueprint $table) {
            $table->string('author_type', 10)->default('admin')->after('type');
            $table->unsignedBigInteger('author_user_id')->nullable()->after('author_type');
            $table->text('rejected_reason')->nullable()->after('save_count');
            $table->json('approved_snapshot')->nullable()->after('rejected_reason');

            $table->foreign('author_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('author_user_id');
        });

        // 2) status enum 확장: draft,published → draft,pending,approved,rejected,suspended
        DB::statement("ALTER TABLE curations MODIFY COLUMN status ENUM('draft','published','pending','approved','rejected','suspended') NOT NULL DEFAULT 'draft'");

        // 3) 기존 published → approved 백필
        DB::table('curations')->where('status', 'published')->update([
            'status' => 'approved',
            'author_type' => 'admin',
        ]);
        DB::table('curations')->where('author_type', '!=', 'admin')->orWhereNull('author_type')->update([
            'author_type' => 'admin',
        ]);

        // 4) 이제 'published' enum 값 제거
        DB::statement("ALTER TABLE curations MODIFY COLUMN status ENUM('draft','pending','approved','rejected','suspended') NOT NULL DEFAULT 'draft'");

        // 5) 신고 테이블
        Schema::create('curation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->string('reason', 50);
            $table->text('detail')->nullable();
            $table->timestamps();

            $table->foreign('reporter_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('curation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curation_reports');

        DB::statement("ALTER TABLE curations MODIFY COLUMN status ENUM('draft','published','pending','approved','rejected','suspended') NOT NULL DEFAULT 'draft'");
        DB::table('curations')->where('status', 'approved')->update(['status' => 'published']);
        DB::statement("ALTER TABLE curations MODIFY COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft'");

        Schema::table('curations', function (Blueprint $table) {
            $table->dropForeign(['author_user_id']);
            $table->dropIndex(['author_user_id']);
            $table->dropColumn(['author_type', 'author_user_id', 'rejected_reason', 'approved_snapshot']);
        });
    }
};
