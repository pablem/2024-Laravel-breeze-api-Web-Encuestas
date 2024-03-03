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
        Schema::create('encuestados', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Encuesta::class);
            $table->string('correo');
            $table->timestamps();
            // $table->unique(['id_encuesta', 'correo'], 'unq_encuestados');
        });
    }

        // controlar que la combinación de correo y encuesta sea única?
        // para mi debe haber una tabla encuestado y una encuesta_respuesta
        // siendo pk(id_encuestado,id_encuesta)

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestados');
    }
};
