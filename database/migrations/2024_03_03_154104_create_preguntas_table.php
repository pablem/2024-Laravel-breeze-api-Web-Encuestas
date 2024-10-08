<?php

use App\Models\Encuesta;
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
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Encuesta::class);
            $table->integer("id_orden")->nullable();
            $table->string('titulo_pregunta', 120);
            $table->string('tipo_pregunta');
            $table->json('seleccion')->nullable();
            $table->json('rango_puntuacion')->nullable();
            $table->boolean('es_obligatoria')->default(false);
            $table->timestamps();
            // Crear índices
            $table->index('id');
            $table->index('encuesta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
