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
        Schema::create('encuestados', function (Blueprint $table) {
            $table->id();
            $table->string('correo')->unique()->nullable();
            $table->string('ip_identificador')->unique()->nullable();
            $table->unsignedSmallInteger('validacion')->nullable();
            $table->timestamps();
            //Indices
            $table->index('id');
            $table->index('correo');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestados');
    }
};
