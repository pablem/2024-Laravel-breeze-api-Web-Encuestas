<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PreguntaController extends Controller
{
   
    /**
     * Actualiza y a la vez almacena nuevas preguntas de una encuesta dada
     * 
     * @param  int  $encuestaId
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request, $encuestaId)
    {
        try {
            // Iniciar una transacción ya que estamos trabajando con múltiples consultas
            DB::beginTransaction();

            foreach ($request->json() as $preguntaData) {

                $validator = Validator::make($preguntaData, [
                        'titulo_pregunta' => 'required|string',
                        'tipo_pregunta' => 'required|string',
                        'seleccion' => 'nullable|array',
                        'rango_puntuacion' => 'nullable|array',
                        // '*.opciones' => ['array', 'required_if:*.tipo_pregunta,3'], // Opcionalmente requerido solo si el tipo es "multiple choice"
                    ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }
                if (isset($preguntaData['id']) && $preguntaData['id'] !== null) {
                // se asume que las preguntas nuevas no tendrán un ID asignado, si tiene, se actualizan
                    Pregunta::where('id', $preguntaData['id'])
                        ->update([
                            'id_orden' => $preguntaData['id_orden'],
                            'titulo_pregunta' => $preguntaData['titulo_pregunta'],
                            'tipo_pregunta' => $preguntaData['tipo_pregunta'],
                            'rango_puntuacion' => $preguntaData['rango_puntuacion'] ?? null,
                            'seleccion' => $preguntaData['seleccion'] ?? null,
                            'es_obligatoria' => $preguntaData['es_obligatoria'] ?? false,
                        ]);
                } else {
                    $pregunta = new Pregunta([
                        'encuesta_id' => $encuestaId,
                        'id_orden' => $preguntaData['id_orden'],
                        'titulo_pregunta' => $preguntaData['titulo_pregunta'],
                        'tipo_pregunta' => $preguntaData['tipo_pregunta'],
                        'rango_puntuacion' => $preguntaData['rango_puntuacion'] ?? null,
                        'seleccion' => $preguntaData['seleccion'] ?? null,
                        'es_obligatoria' => $preguntaData['es_obligatoria'] ?? false,
                    ]);
                    $pregunta->save();
                }
            }
            // Confirmar la transacción si todo ha ido bien
            DB::commit();

            return response()->json(['message' => 'Se guardaron las preguntas'], 201);

        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();

            return response()->json(['error' => $e], 500);
        }
    }
    
    /**
     * Mostrar todas las preguntas de una encuesta (Vista del encuestado / o  Vista del editor de encuestas?) 
     * (Formulario para Respuesta@create )
     *
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function getPreguntas($encuestaId)
    {
        $encuesta = Encuesta::find($encuestaId);
        if (!$encuesta) {
            return response()->json(['error' => 'Encuesta no encontrada'], 404);
        }

        $preguntas = Pregunta::where('encuesta_id', $encuestaId)->orderBy('id_orden')->get();

        return response()->json($preguntas, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $preguntaId
     * @return \Illuminate\Http\Response
     */
    public function destroy($preguntaId)
    {
        try {
            $pregunta = Pregunta::findOrFail($preguntaId);
            if (!$pregunta) {
                return response()->json(['error' => 'Pregunta no encontrada'], 404);
            }
            // Obtener el ID de la encuesta antes de eliminar la pregunta
            $encuestaId = $pregunta->encuesta_id;
            // Eliminar la pregunta
            $pregunta->delete();
            return response()->json(['message' => 'Pregunta eliminada con éxito', 'encuestaId' => $encuestaId], 200);
            // Redirigir a la lista actualizada de preguntas correspondientes a la encuesta
            // session(['encuestaId' => $encuestaId]);
            } 
        catch (\Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    }//revisar la petición desde el front antes de modificar: borrar multiples preguntas 
}
