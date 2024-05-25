<?php

namespace App\Models;

use App\Enums\EstadoEncuesta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Encuesta extends Model
{
    use HasFactory;
    protected $fillable = [
        'titulo_encuesta',
        'descripcion',
        'url',
        'estado',
        'fecha_publicacion',
        'fecha_finalizacion',
        'es_privada',
        'es_anonima',
        'version'
    ];

    protected $casts = [
		'estado' => EstadoEncuesta::class,
	];

    // es publicada / porrador / o piloto
    // dias de publicacion
    // dias restantes
    // piloto?? ultima version?

     /**
     * Al momento de crear una encuesta: se guarda la fk user
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($encuesta) {
            //Se checkea si existe un entorno auth() y usa el id de ese usuario, de no haberlo, permite asignar manualmente otro usuario (para testing)
            if (auth()->check()) {
                $encuesta->user_id = auth()->id();
            }
            // $encuesta->titulo_encuesta = 'Encuesta - ' . now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Obtiene el usuario al que pertenece la encuesta
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el número de preguntas
     */
    public function numeroPreguntas()
    {
        return $this->hasMany(Pregunta::class);
    }
}
