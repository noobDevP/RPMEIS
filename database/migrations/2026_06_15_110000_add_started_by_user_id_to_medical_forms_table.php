<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('started_by_user_id')->nullable()->after('started_by');
            $table->index('started_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('medical_forms', function (Blueprint $table) {
            $table->dropIndex(['started_by_user_id']);
            $table->dropColumn('started_by_user_id');
        });
    }
};
