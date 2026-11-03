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
        Schema::table('signing_doctors', function (Blueprint $table) {
            $table->dropColumn('license_number');
            $table->string('afpos')->nullable();
            $table->string('status')->nullable();
            $table->string('position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signing_doctors', function (Blueprint $table) {
            $table->string('license_number')->nullable();
            $table->dropColumn(['afpos', 'status', 'position']);
        });
    }
};
