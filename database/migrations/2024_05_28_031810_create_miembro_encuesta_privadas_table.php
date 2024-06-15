<?php

use App\Models\Encuesta;
use App\Models\Encuestado;
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
        Schema::create('miembro_encuesta_privadas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Encuesta::class)
            ->constrained()
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignIdFor(Encuestado::class)
            ->constrained()
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
            // Establecer clave primaria compuesta
            // $table->primary(['encuestado_id', 'encuesta_id']);
            // Añadir índices únicos adicionales
            $table->unique(['encuestado_id', 'encuesta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuesta_privadas');
    }
};
