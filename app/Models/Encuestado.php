<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encuestado extends Model
{
    use HasFactory;
    protected $fillable = [
        'correo',
        'ip_identificador',
        'validacion'
    ];

    // protected $casts = [
    //     'ip_identificador' => 'hashed',
    // ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'encuestado_id', 'id');
    }

    public function miembroEncuestaPrivadas(): HasMany
    {
        return $this->hasMany(MiembroEncuestaPrivada::class, 'encuestado_id', 'id');
    }
}
