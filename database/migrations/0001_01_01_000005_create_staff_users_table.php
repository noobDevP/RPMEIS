<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_table', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username');
            $table->string('password');
            $table->string('user_username')->nullable();
            $table->string('active_stat')->default('active');
        });

        Schema::create('admin_credential', function (Blueprint $table) {
            $table->id('admin_id');
            $table->string('admin_username');
            $table->string('admin_password');
        });

        Schema::create('place', function (Blueprint $table) {
            $table->id('place_id');
            $table->string('place_name');
        });

        DB::table('admin_credential')->insert([
            'admin_id' => 1,
            'admin_username' => 'ADMIN',
            'admin_password' => bcrypt('admin123'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_table');
        Schema::dropIfExists('admin_credential');
        Schema::dropIfExists('place');
    }
};
