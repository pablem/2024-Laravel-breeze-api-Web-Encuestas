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
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('encuestado_id');
            $table->unsignedInteger('encuesta_id');
            $table->string('tipo_respuesta');
            $table->unsignedTinyInteger('puntuacion')->nullable();
            $table->text('entrada_texto')->nullable();
            $table->json('seleccion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
