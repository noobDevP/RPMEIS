<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients', 'patient_no')->cascadeOnDelete();
            $table->string('station_key', 50);
            $table->string('approval_status', 50)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('remarks')->nullable();
            $table->text('attachment_path')->nullable();
            $table->timestamps();
            
            $table->unique(['patient_id', 'station_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
