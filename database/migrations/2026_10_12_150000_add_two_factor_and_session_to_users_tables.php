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
                if (!Schema::hasColumn('user_table', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable()->after('password');
                }
                if (!Schema::hasColumn('user_table', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(0)->after('two_factor_secret');
                }
                if (!Schema::hasColumn('user_table', 'session_id')) {
                    $table->string('session_id')->nullable()->after('two_factor_enabled');
                }
            });
        }

        if (Schema::hasTable('admin_credential')) {
            Schema::table('admin_credential', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_credential', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable()->after('admin_password');
                }
                if (!Schema::hasColumn('admin_credential', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(0)->after('two_factor_secret');
                }
                if (!Schema::hasColumn('admin_credential', 'session_id')) {
                    $table->string('session_id')->nullable()->after('two_factor_enabled');
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
                $columns = [];
                if (Schema::hasColumn('user_table', 'two_factor_secret')) {
                    $columns[] = 'two_factor_secret';
                }
                if (Schema::hasColumn('user_table', 'two_factor_enabled')) {
                    $columns[] = 'two_factor_enabled';
                }
                if (Schema::hasColumn('user_table', 'session_id')) {
                    $columns[] = 'session_id';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('admin_credential')) {
            Schema::table('admin_credential', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('admin_credential', 'two_factor_secret')) {
                    $columns[] = 'two_factor_secret';
                }
                if (Schema::hasColumn('admin_credential', 'two_factor_enabled')) {
                    $columns[] = 'two_factor_enabled';
                }
                if (Schema::hasColumn('admin_credential', 'session_id')) {
                    $columns[] = 'session_id';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
