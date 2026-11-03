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
        Schema::table('applications', function (Blueprint $table) {
            $table->index('batch', 'idx_applications_batch');
            $table->index('status', 'idx_applications_status');
            $table->index('app_code', 'idx_applications_app_code');
        });

        Schema::table('medical_examinations', function (Blueprint $table) {
            $table->index('overall_stat', 'idx_medical_examinations_overall_stat');
            $table->index('chief_doctor_approval', 'idx_medical_examinations_chief_approval');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index('is_activated', 'idx_patients_is_activated');
            $table->index('birthdate', 'idx_patients_birthdate');
        });

        Schema::table('station_clearances', function (Blueprint $table) {
            $table->index('approval_status', 'idx_station_clearances_approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('station_clearances', function (Blueprint $table) {
            $table->dropIndex('idx_station_clearances_approval_status');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_is_activated');
            $table->dropIndex('idx_patients_birthdate');
        });

        Schema::table('medical_examinations', function (Blueprint $table) {
            $table->dropIndex('idx_medical_examinations_overall_stat');
            $table->dropIndex('idx_medical_examinations_chief_approval');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_batch');
            $table->dropIndex('idx_applications_status');
            $table->dropIndex('idx_applications_app_code');
        });
    }
};
