<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approving_authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rank');
            $table->string('afpos')->nullable();
            $table->string('status')->nullable();
            $table->string('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approving_authorities');
    }
};