<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiembroEncuestaPrivada extends Model
{
    use HasFactory;
    protected $fillable = [
        'encuesta_id',
        'encuestado_id'
    ];

    public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class);
    }
    public function encuestado(): BelongsTo
    {
        return $this->belongsTo(Encuestado::class);
    }
}
