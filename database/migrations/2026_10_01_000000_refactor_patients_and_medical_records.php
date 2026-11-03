<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0. Rename legacy tables
        Schema::rename('patients', 'legacy_patients');
        Schema::rename('evaluations', 'legacy_evaluations');
        Schema::rename('medical_forms', 'legacy_medical_forms');
        
        // Disable foreign keys temporarily for medical_form_assessments modification
        Schema::disableForeignKeyConstraints();

        Schema::rename('medical_form_assessments', 'legacy_medical_form_assessments');
        
        // 1. Lookup Tables
        Schema::create('ranks', function (Blueprint $table) {
            $table->id('rank_id');
            $table->string('rank_name');
            $table->string('rank_code')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id('branch_id');
            $table->string('branch_name');
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id('unit_id');
            $table->foreignId('branch_id')->nullable()->constrained('branches', 'branch_id')->nullOnDelete();
            $table->string('unit_name');
            $table->timestamps();
        });

        Schema::create('races', function (Blueprint $table) {
            $table->id('race_id');
            $table->string('race_name');
            $table->timestamps();
        });

        Schema::create('exam_centers', function (Blueprint $table) {
            $table->id('center_id');
            $table->string('center_name');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id('station_id');
            $table->string('station_key')->unique();
            $table->string('station_name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        // 2. Core Demographics
        Schema::create('patients', function (Blueprint $table) {
            $table->id('patient_id');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birthdate')->nullable();
            $table->enum('sex', ['Male', 'Female', 'Other'])->nullable();
            $table->foreignId('race_id')->nullable()->constrained('races', 'race_id')->nullOnDelete();
            $table->string('place_of_birth')->nullable();
            $table->boolean('is_activated')->default(0);
            $table->timestamps();
        });

        // 3. Patient Profiles
        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->foreignId('patient_id')->primary()->constrained('patients', 'patient_id')->cascadeOnDelete();
            $table->text('current_address')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // 4. Service Records
        Schema::create('service_records', function (Blueprint $table) {
            $table->id('service_id');
            $table->foreignId('patient_id')->constrained('patients', 'patient_id')->cascadeOnDelete();
            $table->string('serial_no')->nullable()->unique();
            $table->foreignId('rank_id')->nullable()->constrained('ranks', 'rank_id')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units', 'unit_id')->nullOnDelete();
            $table->boolean('is_active_duty')->default(1);
            $table->timestamps();
        });

        // 5. Applications
        Schema::create('applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('patient_id')->constrained('patients', 'patient_id')->cascadeOnDelete();
            $table->string('applicant_no')->nullable()->unique();
            $table->string('app_code')->nullable();
            $table->string('batch')->nullable();
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
        });

        // 6. Medical Examinations
        Schema::create('medical_examinations', function (Blueprint $table) {
            $table->id('examination_id');
            $table->foreignId('application_id')->constrained('applications', 'application_id')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('exam_centers', 'center_id')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->string('overall_stat')->default('NOT DONE');
            $table->integer('step')->default(1);
            $table->string('status')->default('draft');
            $table->string('started_by')->nullable();
            $table->json('form_data')->nullable();
            $table->timestamps();
        });

        // 7. Station Clearances (Replaces evaluations/examination_approvals)
        Schema::create('station_clearances', function (Blueprint $table) {
            $table->id('clearance_id');
            $table->foreignId('examination_id')->constrained('medical_examinations', 'examination_id')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->enum('approval_status', ['GO', 'NO GO', 'COMPLIANCE', 'PENDING'])->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('remarks')->nullable();
            $table->string('evaluated_by')->nullable(); // Can be changed to FK once staff tables are normalized
            $table->timestamps();
            
            $table->unique(['examination_id', 'station_id']);
        });

        // 8. Clearance Attachments
        Schema::create('clearance_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->foreignId('clearance_id')->constrained('station_clearances', 'clearance_id')->cascadeOnDelete();
            $table->string('file_name')->nullable();
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->timestamps();
        });

        // 9. Specialty Assessments (Redform specifics)
        Schema::create('medical_form_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained('medical_examinations', 'examination_id')->cascadeOnDelete();
            $table->string('type'); 
            $table->string('assessed_by')->nullable();
            $table->json('assessment_data')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            
            $table->unique(['examination_id', 'type']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::dropIfExists('medical_form_assessments');
        Schema::dropIfExists('clearance_attachments');
        Schema::dropIfExists('station_clearances');
        Schema::dropIfExists('medical_examinations');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('service_records');
        Schema::dropIfExists('patient_profiles');
        Schema::dropIfExists('patients');
        
        Schema::dropIfExists('stations');
        Schema::dropIfExists('exam_centers');
        Schema::dropIfExists('races');
        Schema::dropIfExists('units');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('ranks');

        Schema::rename('legacy_medical_form_assessments', 'medical_form_assessments');
        Schema::rename('legacy_medical_forms', 'medical_forms');
        Schema::rename('legacy_evaluations', 'evaluations');
        Schema::rename('legacy_patients', 'patients');

        Schema::enableForeignKeyConstraints();
    }
};
