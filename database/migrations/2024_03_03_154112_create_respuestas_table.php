<?php

use App\Models\Encuestado;
use App\Models\Pregunta;
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
            $table->foreignIdFor(Pregunta::class);
            $table->foreignIdFor(Encuestado::class);
            $table->unsignedTinyInteger('puntuacion')->nullable();
            $table->json('seleccion')->nullable();
            $table->text('entrada_texto')->nullable();
            // $table->text('feedback_pregunta')->nullable();
            $table->timestamps();
            // Establecer clave primaria compuesta
            // $table->primary(['pregunta_id', 'encuestado_id']);
            // Añadir índices únicos adicionales
            $table->unique(['pregunta_id', 'encuestado_id']);
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
