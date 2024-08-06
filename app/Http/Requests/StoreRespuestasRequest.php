<?php

namespace App\Http\Requests;

use App\Models\Encuesta;
use App\Models\MiembroEncuestaPrivada;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreRespuestasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|integer|exists:preguntas,id',
            'respuestas.*.puntuacion' => 'nullable|integer',
            'respuestas.*.valor_numerico' => 'nullable|float',
            'respuestas.*.entrada_texto' => 'nullable|string',
            'respuestas.*.seleccion' => 'nullable|array',
            'correo' => 'nullable|email',
            'comentarios' => 'nullable|string'
        ];
    }


    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $respuestas = $this->input('respuestas');
            $correo = $this->input('correo');
            $preguntaId = $respuestas[0]['pregunta_id'];
            $encuestaId = Pregunta::where('id', $preguntaId)->value('encuesta_id');
            $encuesta = Encuesta::find($encuestaId);

            if (!$encuesta) {
                $validator->errors()->add('encuesta', 'Encuesta no encontrada.');
                return;
            }

            if ($encuesta->esFinalizada()) {
                $validator->errors()->add('encuesta', 'Encuesta finalizada.');
                return;
            }

            if ($encuesta->limite_respuestas > 0 && $encuesta->numeroRespuestas() >= $encuesta->limite_respuestas) {
                $validator->errors()->add('encuesta', 'Se ha alcanzado el límite de respuestas para esta encuesta.');
                return;
            }

            if ($correo) {
                $respuestaExistente = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
                    ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
                    ->where('preguntas.encuesta_id', $encuestaId)
                    ->where('encuestados.correo', $correo)
                    ->first(['respuestas.*']);
            } else {
                $ipIdentificador = $this->ip();
                $respuestaExistente = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
                    ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
                    ->where('preguntas.encuesta_id', $encuestaId)
                    ->where('encuestados.ip_identificador', $ipIdentificador)
                    ->first(['respuestas.*']);
            }

            if ($respuestaExistente) {
                $validator->errors()->add('encuesta', 'Ya ha respondido esta encuesta.');
                return;
            }

            if ($encuesta->es_privada && $correo) {
                $esMiembro = MiembroEncuestaPrivada::join('encuestados', 'miembro_encuesta_privadas.encuestado_id', '=', 'encuestados.id')
                    ->where('encuestados.correo', $correo)
                    ->where('miembro_encuesta_privadas.encuesta_id', $encuestaId)
                    ->exists();
                if (!$esMiembro) {
                    $validator->errors()->add('encuesta', 'No está autorizado para responder la encuesta privada.');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'respuestas.required' => 'Las respuestas son obligatorias.',
            'respuestas.*.pregunta_id.exists' => 'La pregunta no existe.',
            'correo.email' => 'El correo debe ser una dirección de correo válida.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
