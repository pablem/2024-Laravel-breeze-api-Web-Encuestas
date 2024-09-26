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
            $table->unsignedInteger('id_versionamiento')->nullable();
            $table->foreignIdFor(User::class);
            $table->string('titulo_encuesta', 100)->nullable();
            $table->text('descripcion', 100)->nullable();
            $table->string('url')->nullable()->unique();
            $table->string('estado')->default(EstadoEncuesta::Borrador->value);
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->boolean('es_privada')->default(false);
            $table->boolean('es_anonima')->default(false);
            $table->unsignedTinyInteger('version')->default(1);
            $table->unsignedInteger('limite_respuestas')->default(0);
            $table->timestamps();
            // Crear índices
            $table->index('id');
            $table->index('url');
            $table->index('fecha_publicacion');
            $table->index('fecha_finalizacion');
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
