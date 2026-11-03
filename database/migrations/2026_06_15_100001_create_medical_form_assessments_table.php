<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_form_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_form_id')->constrained('medical_forms')->onDelete('cascade');
            $table->string('type'); // dental, ecg, ocular, hearing, psych
            $table->string('assessed_by')->nullable();
            $table->json('assessment_data')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['medical_form_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_form_assessments');
    }
};