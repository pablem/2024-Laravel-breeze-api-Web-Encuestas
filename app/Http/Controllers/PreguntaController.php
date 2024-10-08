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
            $contador = 1;
            foreach ($request->json() as $preguntaData) {
                if (isset($preguntaData['id'])) {
                    // se asume que las preguntas nuevas no tendrán un campo 'id', si tiene, se actualizan
                    // se asume que algunos atributos pueden no estar, se filtrar los datos nulos
                    $validator = Validator::make($preguntaData, [
                        // 'id_orden' => 'integer',
                        'titulo_pregunta' => 'required',
                        'tipo_pregunta' => 'string',
                        'rango_puntuacion' => 'array',
                        'seleccion' => 'array',
                        'es_obligatoria' => 'boolean',
                    ]);
                    if ($validator->fails()) {
                        return response()->json(['message' => implode(', ', $validator->errors()->all())], 400);
                    }
                    $updateData = array_filter([
                        'id_orden' => $contador,
                        // 'id_orden' => $preguntaData['id_orden'] ?? null,
                        'titulo_pregunta' => $preguntaData['titulo_pregunta'] ?? null,
                        'tipo_pregunta' => $preguntaData['tipo_pregunta'] ?? null,
                        'rango_puntuacion' => $preguntaData['rango_puntuacion'] ?? null,
                        'seleccion' => $preguntaData['seleccion'] ?? null,
                        'es_obligatoria' => $preguntaData['es_obligatoria'] ?? null,
                    ], function ($value) {
                        return !is_null($value);
                    });

                    Pregunta::where('id', $preguntaData['id'])->update($updateData);

                } else {
                    $validator = Validator::make($preguntaData, [
                        // 'id_orden' => 'required|integer',
                        'titulo_pregunta' => 'required',
                        'tipo_pregunta' => 'required|string',
                        'rango_puntuacion' => 'nullable|array',
                        'seleccion' => 'nullable|array',
                        'es_obligatoria' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return response()->json(['errors' => implode(', ', $validator->errors()->all())], 400);
                    }
                    $pregunta = new Pregunta([
                        'encuesta_id' => $encuestaId,
                        'id_orden' => $contador,
                        // 'id_orden' => $preguntaData['id_orden'],
                        'titulo_pregunta' => $preguntaData['titulo_pregunta'],
                        'tipo_pregunta' => $preguntaData['tipo_pregunta'],
                        'rango_puntuacion' => $preguntaData['rango_puntuacion'] ?? null,
                        'seleccion' => $preguntaData['seleccion'] ?? null,
                        'es_obligatoria' => $preguntaData['es_obligatoria'] ?? false,
                    ]);
                    $pregunta->save();
                }
                $contador++;
            }

            //La fecha 'updated_at' de encuesta se actualiza con la modificación de alguna pregunta
            Encuesta::where('id', $encuestaId)->update(['id' => $encuestaId]);
            
            // Confirmar la transacción si todo ha ido bien
            DB::commit();

            return response()->json(['success' => 'Se guardaron las preguntas'], 201);
        
        } catch (\Throwable $th) {

            // Deshacer la transacción en caso de error
            DB::rollBack();
            return response()->json(['error ' => $th->getMessage()], 500);
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
            $pregunta = Pregunta::find($preguntaId);
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
        } catch (\Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    } //revisar la petición desde el front antes de modificar: borrar multiples preguntas 
}
