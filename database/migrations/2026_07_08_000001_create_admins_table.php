<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('login_id')->unique();
            $table->string('name');
            $table->string('password');
            $table->string('role')->default('admin'); // super, admin
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        DB::table('admins')->insert([
            'login_id' => 'root',
            'name' => '최고관리자',
            'password' => Hash::make('pinpick'),
            'role' => 'super',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
