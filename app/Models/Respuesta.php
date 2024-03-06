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
        'tipo_respuesta',
        'puntuacion',
        'entrada_texto',
        'seleccion',
    ];

    protected $casts = [
        'seleccion' => 'json',
        'tipo_respuesta' => TipoPregunta::class,
    ];
}
