<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('medical_examinations', function (Blueprint $table) {
            $table->string('chief_doctor_approval')->nullable()->after('overall_stat');
        });
    }
    public function down(): void {
        Schema::table('medical_examinations', function (Blueprint $table) {
            $table->dropColumn('chief_doctor_approval');
        });
    }
};
