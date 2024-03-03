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
        Schema::table('encuestados', function (Blueprint $table) {
            // Definir la combinación única
            $table->unique(['encuesta_id', 'correo'], 'unq_encuestados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encuestados', function (Blueprint $table) {
            $table->dropUnique('unq_encuestados');
        });
    }
};
