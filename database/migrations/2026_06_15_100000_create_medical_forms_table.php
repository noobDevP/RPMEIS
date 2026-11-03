<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_forms', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_no')->index();
            $table->integer('step')->default(1);
            $table->json('form_data')->nullable();
            $table->json('form_data_snapshot')->nullable();
            $table->string('status')->default('draft');
            $table->string('started_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_forms');
    }
};