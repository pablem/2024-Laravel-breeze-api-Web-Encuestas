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
        'seleccion',
        'puntuacion',
        'entrada_texto',
        'feedback_pregunta'
    ];

    protected $casts = [
        'seleccion' => 'json',
    ];

    public function esRespuestaVacia()
    {
        return is_null($this->puntuacion) && is_null($this->entrada_texto) && (is_null($this->seleccion) || empty($this->seleccion));
    }
}
