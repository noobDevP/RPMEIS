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
        if (Schema::hasTable('user_table')) {
            Schema::table('user_table', function (Blueprint $table) {
                if (!Schema::hasColumn('user_table', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
                }
            });
        }

        if (Schema::hasTable('admin_credential')) {
            Schema::table('admin_credential', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_credential', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_table')) {
            Schema::table('user_table', function (Blueprint $table) {
                if (Schema::hasColumn('user_table', 'two_factor_recovery_codes')) {
                    $table->dropColumn('two_factor_recovery_codes');
                }
            });
        }

        if (Schema::hasTable('admin_credential')) {
            Schema::table('admin_credential', function (Blueprint $table) {
                if (Schema::hasColumn('admin_credential', 'two_factor_recovery_codes')) {
                    $table->dropColumn('two_factor_recovery_codes');
                }
            });
        }
    }
};
