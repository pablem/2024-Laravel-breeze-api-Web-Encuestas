<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encuestado extends Model
{
    // use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'correo',
    ];
}
