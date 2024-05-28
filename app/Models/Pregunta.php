<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\TipoPregunta;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    // use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'id_orden',
        'titulo_pregunta',
        'tipo_pregunta',
        'seleccion',
        'rango_puntuacion',
        'esObligatoria'
    ];
    protected $casts = [
        'rango_puntuacion' => 'json',
        'seleccion' => 'json',
        'tipo_pregunta' => TipoPregunta::class,
    ];
}
