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
            $table->boolean('esObligatoria');
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
