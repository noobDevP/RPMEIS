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
        Schema::table('user_table', function (Blueprint $table) {
            $table->string('rank')->nullable();
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
        Schema::table('user_table', function (Blueprint $table) {
            $table->dropColumn(['rank', 'afpos', 'status', 'position']);
        });
    }
};
