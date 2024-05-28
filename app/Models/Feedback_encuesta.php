<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback_encuesta extends Model
{
    // use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'indice_satisfaccion',
        'comentarios'
    ];
}
