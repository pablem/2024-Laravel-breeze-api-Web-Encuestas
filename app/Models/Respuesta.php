<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\TipoPregunta;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model
{
    // use HasFactory;
    protected $fillable = [
        'encuestado_id',
        'pregunta_id',
        // 'puntuacion',
        'entrada_texto',
        'seleccion',
        // 'feedback_pregunta'
    ];

    protected $casts = [
        'seleccion' => 'json',
        'tipo_respuesta' => TipoPregunta::class,
    ];
}
