<?php

namespace App\Models;

use App\Enums\EstadoEncuesta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Encuesta extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'id_versionamiento',
        'titulo_encuesta',
        'descripcion',
        'url',
        'estado',
        'fecha_publicacion',
        'fecha_finalizacion',
        'es_privada',
        'es_anonima',
        'version',
        'limite_respuestas'
    ];

    protected $casts = [
        'estado' => EstadoEncuesta::class,
    ];

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
        static::created(function ($encuesta) {
            // Control de versión
            if (is_null($encuesta->version) || $encuesta->version == 1) {
                $encuesta->version = 1;
                $encuesta->id_versionamiento = $encuesta->id;
                $encuesta->save();
            }
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
     * Obtiene: preguntas / miembros privados / feedback asociados a la encuesta
     */
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'encuesta_id', 'id');
    }
    public function miembroEncuestaPrivadas(): HasMany
    {
        return $this->hasMany(MiembroEncuestaPrivada::class, 'encuesta_id', 'id');
    }
    public function feedbackEncuesta(): HasMany
    {
        return $this->hasMany(Feedback_encuesta::class, 'encuesta_id', 'id');
    }

    /**
     * Verifica si el correo es miembro de la encuesta privada 
     */
    public function esMiembro(string $email): bool
    {
        return $this->hasMany(MiembroEncuestaPrivada::class, 'encuesta_id', 'id')
            ->whereHas('encuestado', function ($query) use ($email) {
                $query->where('correo', $email);
            })
            ->exists();
    }

    /**
     * Verifica si la encuesta está finalizada
     */
    public function esFinalizada(): bool
    {
        if (!$this->fecha_finalizacion) {
            return false;
        }

        return $this->fecha_finalizacion <= now();
    }

    /**
     * Calcula los días que lleva publicada la encuesta
     */
    // public function diasPublicada(): int
    // {
    //     if (!$this->fecha_publicacion) {
    //         return 0;
    //     }

    //     return $this->fecha_publicacion->diffInDays(now());
    // }

    /**
     * Calcula los días restantes para la fecha de finalización de la encuesta
     */
    public function diasRestantes(): ?int
    {
        if (!$this->fecha_finalizacion) {
            return null;
        }
        $diasRestantes = now()->diffInDays($this->fecha_finalizacion, false);
        return $diasRestantes >= 0 ? $diasRestantes : null;
    }

    /**
     * Devuelve número de encuestados que han respondido a una encuesta
     */
    public function numeroRespuestas(): int
    {
        return $this->hasManyThrough(Respuesta::class, Pregunta::class)
            ->distinct('encuestado_id')
            ->count('encuestado_id');
    }

    /**
     * Verifica si esta encuesta es la última versión del id_versionamiento
     */
    // public function es_ultima_version(): bool
    // {
    //     // Buscar otras encuestas con el mismo id_versionamiento
    //     $otrasEncuestas = self::where('id_versionamiento', $this->id_versionamiento)
    //         ->where('id', '!=', $this->id) // Excluir la encuesta actual
    //         ->get();

    //     // Si no hay otras encuestas, esta es la última versión
    //     if ($otrasEncuestas->isEmpty()) {
    //         return true;
    //     }

    //     // Obtener la versión más alta entre las otras encuestas
    //     $maxVersion = $otrasEncuestas->max('version');

    //     // Comparar la versión actual con la versión más alta encontrada
    //     return $this->version >= $maxVersion;
    // }
}
