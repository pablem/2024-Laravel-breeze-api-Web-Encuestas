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
        Schema::create('feedback_encuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Encuesta::class)->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedSmallInteger('indice_satisfaccion')->nullable();//No se usa
            $table->string('comentarios')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_encuestas');
    }
};
