<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id('log_id');
                $table->unsignedBigInteger('patient_no_logs')->nullable();
                $table->string('firstName_logs')->nullable();
                $table->string('lastName_logs')->nullable();
                $table->string('approval_stat_dental_logs')->nullable();
                $table->dateTime('timestamp_dental_logs')->nullable();
                $table->string('approval_optha_eent_logs')->nullable();
                $table->dateTime('timestamp_ophtha_eent_logs')->nullable();
                $table->string('approval_stat_ecg_xray_logs')->nullable();
                $table->dateTime('timestamp_ecg_xray_logs')->nullable();
                $table->string('approval_stat_lab_drugtest_logs')->nullable();
                $table->dateTime('timestamp_drugtest_laboratory_logs')->nullable();
                $table->string('approval_stat_np')->nullable();
                $table->dateTime('timestamp_np_logs')->nullable();
                $table->string('approval_stat_gpe')->nullable();
                $table->dateTime('timestamp_gpe')->nullable();
                $table->string('user_username')->nullable();
            });
        }

        if (!Schema::hasTable('logs_doctors')) {
            Schema::create('logs_doctors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_no_logs')->nullable();
                $table->string('firstName_logs')->nullable();
                $table->string('lastName_logs')->nullable();
                $table->string('approval_stat_dental_logs')->nullable();
                $table->dateTime('timestamp_dental_logs')->nullable();
                $table->string('approval_optha_eent_logs')->nullable();
                $table->dateTime('timestamp_ophtha_eent_logs')->nullable();
                $table->string('approval_stat_ecg_xray_logs')->nullable();
                $table->dateTime('timestamp_ecg_xray_logs')->nullable();
                $table->string('approval_stat_lab_drugtest_logs')->nullable();
                $table->dateTime('timestamp_drugtest_laboratory_logs')->nullable();
                $table->string('approval_stat_np')->nullable();
                $table->dateTime('timestamp_np_logs')->nullable();
                $table->string('approval_stat_gpe')->nullable();
                $table->dateTime('timestamp_gpe')->nullable();
                $table->string('user_username')->nullable();
            });
        }

        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->text('message')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('logs_doctors');
        Schema::dropIfExists('logs');
    }
};
