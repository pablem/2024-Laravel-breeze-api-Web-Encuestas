<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\TipoPregunta;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    // use HasFactory;
    protected $fillable = [
        'titulo_pregunta',
        'tipo_pregunta',
        'numero_pregunta',
        'seleccion',
        'rango_puntuacion',
    ];
    protected $casts = [
        'rango_puntuacion' => 'json',
        'opciones' => 'json',
        'tipo_pregunta' => TipoPregunta::class,
    ];
}
