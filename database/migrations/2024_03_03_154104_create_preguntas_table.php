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
            $table->string('titulo_pregunta', 120);
            // $table->enum('tipo_pregunta', ['text', 'multiple choice', 'unique choice', 'list', 'rating']);
            $table->string('tipo_pregunta');
            $table->smallInteger('numero_pregunta')->nullable();
            $table->json('seleccion')->nullable();
            $table->json('rango_puntuacion')->nullable();
            $table->timestamps();
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
