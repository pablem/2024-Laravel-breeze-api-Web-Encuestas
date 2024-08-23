<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback_encuesta extends Model
{
    use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'indice_satisfaccion',//No se usa
        'comentarios'
    ];

    public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class);
    }
}
