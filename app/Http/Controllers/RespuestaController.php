<?php

namespace App\Http\Controllers;

use App\Models\Encuestado;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RespuestaController extends Controller
{

    /** (MODULARIZAR?)
     * Almacenar en BD las respuestas de un encuestado
     *
     * @param  int  $encuestaId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $encuestaId)
    {
        // Validación del campo "correo" + combinación única corre&encuesta_id
        // $emailValidator = Validator::make($request->all(), [
        //     'correo' => 'required|email|unique:respuestas,correo,NULL,id,encuesta_id,' . $encuestaId,
        // ]);
        // if ($emailValidator->fails()) {
        //     return response()->json(['error' => $emailValidator->errors()], 400);
        // }

        try {

            DB::beginTransaction();

            // Creación y almacenamiento de "encuestado"
            // $encuestado = Encuestado::create([
            //     'correo' => $request->correo,
            //     'encuesta_id' => $encuestaId,
            // ]);
            
            // Guardado de las respuestas
            // foreach ($request->respuestas as $respuestaData) {
            foreach ($request->json() as $respuestaData) {

                $validator = Validator::make($respuestaData, [
                    'tipo_respuesta' => 'required|string',
                    'puntuacion' => 'nullable|integer',
                    'entrada_texto' => 'nullable|string',
                    'seleccion' => 'nullable|array',
                    // '*.opciones' => ['array', 'required_if:*.tipo_respuesta,3'], // Opcionalmente requerido solo si el tipo es "multiple choice"
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }
                $pregunta = new Respuesta([
                    'encuestado_id' => 1,//$encuestado->id,
                    'tipo_respuesta' => $respuestaData['tipo_respuesta'],
                    'puntuacion' => $respuestaData['puntuacion'] ?? null,
                    'entrada_texto' => $respuestaData['entrada_texto'] ?? null,
                    'seleccion' => $respuestaData['seleccion'] ?? null,
                ]);
                $pregunta->save();
            }
            // Confirmar la transacción si todo ha ido bien
            DB::commit();
            return response()->json(['message' => 'Se guardó su respuesta'], 201);

        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            return response()->json(['error' => $e], 500);
        }
    }

    /**
     * Sección Informes:
     * 1) DEVOLVER TODAS LAS RESPUESTAS DE UNA PREGUNTA EN ESPECÍFICO: HAY UNA FALLA DE ARQ DE LA BASE DE DATOS ?
     * (o número de opciones seleccionadas, número de caracteres del texto, etc...)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

}
