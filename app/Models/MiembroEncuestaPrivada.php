<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiembroEncuestaPrivada extends Model
{
    use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'encuestado_id'
    ];
}
