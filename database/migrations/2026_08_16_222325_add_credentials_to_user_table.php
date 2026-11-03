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
            $table->string('credential_name')->nullable()->after('user_username');
            $table->string('credential_title')->nullable()->after('credential_name');
            $table->string('credential_license')->nullable()->after('credential_title');
            $table->string('credential_status')->nullable()->after('credential_license');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_table', function (Blueprint $table) {
            //
        });
    }
};
