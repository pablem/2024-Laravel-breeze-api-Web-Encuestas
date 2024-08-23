<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encuestado extends Model
{
    use HasFactory;
    protected $fillable = [
        'correo',
        'ip_identificador'
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'encuestado_id', 'id');
    }

    // public function encuestas()
    // {
    //     return $this->hasManyThrough(
    //         Encuesta::class, 
    //         Respuesta::class, 
    //         'encuestado_id', // Foreign key on the respuestas table...
    //         'id', // Foreign key on the encuestas table...
    //         'id', // Local key on the encuestados table...
    //         'encuesta_id' // Local key on the respuestas table...
    //     )->distinct();
    // }

    // public function miembroEncuestaPrivadas()
    // {
    //     return $this->belongsToMany(MiembroEncuestaPrivada::class, 'miembro_encuesta_privadas', 'encuestado_id', 'id');
    // }

    public function miembroEncuestaPrivadas(): HasMany
    {
        return $this->hasMany(MiembroEncuestaPrivada::class, 'encuestado_id', 'id');
    }
}
