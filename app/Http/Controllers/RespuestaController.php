<?php

namespace App\Http\Controllers;

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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //cambio a discutir: agregar $encuestaId a la solicitud  
    {
        try {
            DB::beginTransaction();

            // Creación y almacenamiento de "encuestado"
            if ($request->has('correo')) {
                $encuestado = Encuestado::where('correo', $request->correo)->first();
                if (!$encuestado) {
                    $encuestadoData['correo'] = $request->correo;
                    $encuestado = Encuestado::create($encuestadoData);
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
                $validator = Validator::make($respuestaData, [
                    'pregunta_id' => 'required|integer',        //Se consideran también ids con campos nulos (sin responder)
                    'puntuacion' => 'nullable|integer',
                    'entrada_texto' => 'nullable|string',
                    'seleccion' => 'nullable|array',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }

                // Crear la respuesta
                $respuesta = new Respuesta([
                    'encuestado_id' => $encuestado->id,
                    'pregunta_id' => $respuestaData['pregunta_id'],
                    'puntuacion' => $respuestaData['puntuacion'] ?? null,
                    'entrada_texto' => $respuestaData['entrada_texto'] ?? null,
                    'seleccion' => $respuestaData['seleccion'] ?? null,
                ]);

                // Obtener la pregunta para verificar si es obligatoria
                // $pregunta = Pregunta::find($respuestaData['pregunta_id']);
                // if ($pregunta && $pregunta->es_obligatoria) {
                //     // Validar que la respuesta no está vacía
                //     if ($respuesta->esVacia()) {
                //         return response()->json(['error' => 'La pregunta es obligatoria y uno de sus campos debe ser no nulo.'], 400);
                //     }
                // }
                
                $respuesta->save();
            }
            //Encuesta PILOTO
            if ($request->has('comentarios')) {
                $feedBackData['comentarios'] = $request->comentarios; 

                //en el caso que la solicitud no tenga encuestaId, se la obtiene a partir de la pregunta_id:
                $preguntaId = $request->input('respuestas')[0]['pregunta_id'];
                $encuestaId = Pregunta::where('id', $preguntaId)->value('encuesta_id');
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
