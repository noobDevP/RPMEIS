<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id('patient_no');
            $table->string('status')->default('active');
            $table->string('overall_stat')->default('NOT DONE');
            $table->string('firstName')->nullable();
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('appcode')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('applicant_no')->nullable();
            $table->string('rank')->nullable();
            $table->string('serialNo')->nullable();
            $table->string('branch')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('age')->nullable();
            $table->string('sex')->nullable();
            $table->string('raceSelect')->nullable();
            $table->string('placeOfBirth')->nullable();
            $table->string('currentAddress')->nullable();
            $table->string('placeOfExamination')->nullable();
            $table->string('dateOfExamination')->nullable();
            $table->string('purpose')->nullable();
            $table->string('batch')->nullable();
            $table->string('unitorg')->nullable();
            $table->string('otherData')->nullable();
            $table->string('chief_doctor_approval')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
