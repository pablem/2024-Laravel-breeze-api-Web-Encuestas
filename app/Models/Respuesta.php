<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    ];

    protected $casts = [
        'seleccion' => 'json',
    ];

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id', 'id');
    }

    public function esRespuestaVacia()
    {
        return is_null($this->puntuacion) && is_null($this->entrada_texto) && (is_null($this->seleccion) || empty($this->seleccion));
    }
}
