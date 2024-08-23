<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\TipoPregunta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pregunta extends Model
{
    use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'id_orden',
        'titulo_pregunta',
        'tipo_pregunta',
        'seleccion',
        'rango_puntuacion',
        'es_obligatoria'
    ];
    protected $casts = [
        'rango_puntuacion' => 'json',
        'seleccion' => 'json',
        'tipo_pregunta' => TipoPregunta::class,
    ];

    public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'pregunta_id', 'id');
    }
}
