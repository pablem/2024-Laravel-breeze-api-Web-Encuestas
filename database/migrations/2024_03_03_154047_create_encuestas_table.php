<?php

use App\Enums\EstadoEncuesta;
use App\Models\User;
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
        Schema::create('encuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('titulo_encuesta', 40)->nullable();
            $table->text('descripcion', 100)->nullable();
            $table->string('url')->nullable();
            // $table->enum('estado', ['borrador', 'piloto', 'publicada']);
            $table->string('estado')->default(EstadoEncuesta::Borrador->value);
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            // $table->boolean('es_borrador')->nullable()->default(true);
            $table->boolean('es_privada')->default(false);
            $table->boolean('es_anonima')->default(false);
            $table->unsignedTinyInteger('version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
