<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRespuestasRequest;
use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\Feedback_encuesta;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RespuestaController extends Controller
{
    /** 
     * Almacenar en BD las respuestas de un encuestado
     *
     * @param  App\Http\Requests\StoreRespuestasRequest $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRespuestasRequest $request, $encuestaId)
    {
        try {
            DB::beginTransaction();
            $encuesta = Encuesta::select('id','es_anonima')->where('id', $encuestaId)->first();
            if(!$encuesta) {
                return response()->json(['message' => 'Encuesta no encontrada'], 404);
            }
            // Creación y almacenamiento de "encuestado"
            if(!$encuesta->es_anonima) {
                if ($request->has('correo')) { //se puede mandar simplemente el id 
                    $encuestado = Encuestado::where('correo', $request->correo)->first();
                    if (!$encuestado) {
                        return response()->json(['message' => 'No se proporcionó un correo válido.'], 403);
                    } else {
                        $validacion = $encuestado->validacion !== null ? $encuestado->validacion + 1 : 1;
                        $encuestado->update([
                            'validacion' => $validacion,
                        ]);
                    }
                } else {
                    return response()->json(['message' => 'No se proporcionó ningún correo.'], 403);
                }
            } else {
                $ipIdentificador = $request->ip();
                $encuestado = Encuestado::where('ip_identificador', $ipIdentificador)->first();
                if (!$encuestado) {
                    $encuestadoData['ip_identificador'] = $ipIdentificador;
                    $encuestado = Encuestado::create($encuestadoData);
                }
            }
            // Guardado de las respuestas
            foreach ($request->respuestas as $respuestaData) {
                $respuesta = new Respuesta([
                    'encuestado_id' => $encuestado->id,
                    'pregunta_id' => $respuestaData['pregunta_id'],
                    'puntuacion' => $respuestaData['puntuacion'] ?? null,
                    'entrada_texto' => $respuestaData['entrada_texto'] ?? null,
                    'seleccion' => $respuestaData['seleccion'] ?? null,
                ]);
                // HACER ESTO MÁS EFICIENTE
                //Obtener la pregunta para verificar si es obligatoria
                // $pregunta = Pregunta::select('id', 'es_obligatoria')->find($respuestaData['pregunta_id']);
                // if ($pregunta && $pregunta->es_obligatoria) {
                //     // Validar que la respuesta no está vacía
                //     if ($respuesta->esRespuestaVacia()) {
                //         return response()->json(['error' => 'La pregunta' . $pregunta->id . 'es obligatoria y uno de sus campos debe ser no nulo.'], 422);
                //     }
                // }
                $respuesta->save();
            }
            //Encuesta PILOTO
            if ($request->has('comentarios')) {
                $feedBackData['comentarios'] = $request->comentarios; 

                //en el caso que la solicitud no tenga encuestaId, se la obtiene a partir de la pregunta_id:
                // $preguntaId = $request->input('respuestas')[0]['pregunta_id'];
                // $encuestaId = Pregunta::where('id', $preguntaId)->value('encuesta_id');
                $feedBackData['encuesta_id'] = $encuestaId;
                Feedback_encuesta::create($feedBackData);
            }
            // Confirmar la transacción si todo ha ido bien
            DB::commit();
            return response()->json(['message' => 'Se guardó su respuesta'], 201);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
