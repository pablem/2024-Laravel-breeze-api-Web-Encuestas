<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encuestado extends Model
{
    // use HasFactory;
    protected $fillable = [
        'correo',
        'ip_identificador'
    ];
}
