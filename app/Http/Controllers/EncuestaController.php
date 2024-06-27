<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEncuesta;
use App\Models\Encuesta;
use App\Models\MiembroEncuestaPrivada;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EncuestaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $encuestas = Encuesta::with('user:id,name')->orderBy('created_at', 'desc')->get();
        return response()->json($encuestas, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // frontend
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo_encuesta' => 'required|string|max:100|unique:encuestas,titulo_encuesta',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $encuesta = Encuesta::create($request->all());
            return response()->json($encuesta, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Muestra una encuesta a partir de la url amigable (slug)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     */
    public function show($slug, Request $request)
{
    try {
        // Obtener título y dirección de la URL
        $arraySlug = explode('-', $slug);
        $version = end($arraySlug);
        $encuesta = Encuesta::where('url', 'like', "%{$slug}%")->where('version', $version)->first();
        if (!$encuesta) {
            return response()->json(['code' => 'ENCUESTA_NO_ENCONTRADA', 'message' => 'Encuesta no encontrada'], 404);
        }
        if ($encuesta->es_finalizada()) {
            return response()->json(['code' => 'ENCUESTA_FINALIZADA', 'message' => 'Encuesta finalizada'], 200);
        }
        if ($encuesta->limite_respuestas > 0 && $encuesta->numeroRespuestas() >= $encuesta->limite_respuestas) {
            return response()->json(['code' => 'LIMITE_RESPUESTAS_ALCANZADO', 'message' => 'Se ha alcanzado el límite de respuestas para esta encuesta.'], 200);
        }
        if (!$encuesta->es_anonima) {
            return response()->json(['code' => 'ENCUESTA_NO_ANONIMA', 'message' => 'Encuesta no anónima. Debe identificarse con un correo electrónico válido.'], 200);
        }
        $ip = $request->ip();
        $respuestaExistente = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
            ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
            ->where('preguntas.encuesta_id', $encuesta->id)
            ->where('encuestados.ip_identificador', $ip)
            ->first(['respuestas.*']);
        if ($respuestaExistente) {
            return response()->json(['code' => 'ENCUESTA_YA_RESPONDIDA', 'message' => 'Ud. ya ha respondido esta encuesta.'], 200);
        }
        return response()->json(['code' => 'ENCUESTA_DISPONIBLE', 'encuesta' => $encuesta], 200);
    } catch (\Throwable $th) {
        return response()->json(['code' => 'ERROR_SERVIDOR', 'message' => $th->getMessage()], 500);
    }
}

public function showByMail($slug, Request $request)
{
    try {
        $correo = $request->input('correo');
        $arraySlug = explode('-', $slug);
        $version = end($arraySlug);
        $encuesta = Encuesta::where('url', 'like', "%{$slug}%")->where('version', $version)->first();
        if (!$encuesta) {
            return response()->json(['code' => 'ENCUESTA_NO_ENCONTRADA', 'message' => 'Encuesta no encontrada'], 404);
        }
        if ($encuesta->es_finalizada()) {
            return response()->json(['code' => 'ENCUESTA_FINALIZADA', 'message' => 'Encuesta finalizada'], 200);
        }
        if ($encuesta->limite_respuestas > 0 && $encuesta->numeroRespuestas() >= $encuesta->limite_respuestas) {
            return response()->json(['code' => 'LIMITE_RESPUESTAS_ALCANZADO', 'message' => 'Se ha alcanzado el límite de respuestas para esta encuesta.'], 200);
        }
        if ($encuesta->es_anonima) {
            return response()->json(['code' => 'ENCUESTA_ANONIMA', 'message' => 'Esta encuesta es anónima. Ingresar sin correo.'], 200);
        }
        if ($encuesta->es_privada) {
            $esMiembro = MiembroEncuestaPrivada::join('encuestados', 'miembro_encuesta_privadas.encuestado_id', '=', 'encuestados.id')
                ->where('encuestados.correo', $correo)
                ->where('miembro_encuesta_privadas.encuesta_id', $encuesta->id)
                ->exists();
            if (!$esMiembro) {
                return response()->json(['code' => 'ENCUESTA_PRIVADA', 'message' => 'Esta encuesta es privada. Ud. no está autorizado para responder.'], 403);
            }
        }
        $respuestaExistente = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
            ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
            ->where('preguntas.encuesta_id', $encuesta->id)
            ->where('encuestados.correo', $correo)
            ->first(['respuestas.*']);
        if ($respuestaExistente) {
            return response()->json(['code' => 'ENCUESTA_YA_RESPONDIDA', 'message' => 'Ud. ya ha respondido esta encuesta.'], 200);
        }
        return response()->json(['code' => 'ENCUESTA_DISPONIBLE', 'encuesta' => $encuesta], 200);
    } catch (\Throwable $th) {
        return response()->json(['code' => 'ERROR_SERVIDOR', 'message' => $th->getMessage()], 500);
    }
}

    /**
     * Show the form for editing the specified resource.
     * @param  int  $encuestaId
     * 
     */
    public function edit($encuestaId)
    {
        $encuesta = Encuesta::find($encuestaId);
        if (is_null($encuesta)) {
            return response()->json(['error' => 'Encuesta no encontrada'], 404);
        }
        // if ($encuesta->estado === EstadoEncuesta::Borrador->value) {
        //     return response()->json($encuesta, 200);
        // } else {
        //     return response()->json(['error' => 'No se puede editar la encuesta. No es "Borrador".'], 403);
        // }
        return response()->json($encuesta, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $encuestaId)
    {
        $encuesta = Encuesta::find($encuestaId);
        if (!$encuesta) {
            return response()->json(['error' => 'Encuesta no encontrada'], 404);
        }
        $validator = Validator::make($request->all(), [
            'titulo_encuesta' => 'required|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $encuesta->update($request->all());
        return response()->json($encuesta, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function destroy($encuestaId)
    {
        $encuesta = Encuesta::find($encuestaId);
        if (!$encuesta) {
            return response()->json(['error' => 'Encuesta no encontrada'], 404);
        }
        $encuesta->delete();
        return response()->json(['message' => 'Encuesta eliminada con éxito'], 200);
    }

    /**
     * Crea nuevo borrador a partir de un id de encuesta
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function nuevaVersion($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId);
            $borrador = new Encuesta([
                'user_id' => $encuesta->user_id, // borrar a la hora de implementar el frontend  
                'id_versionamiento' => $encuesta->id_versionamiento,
                'titulo_encuesta' => $encuesta->titulo_encuesta, // . ' version ' . ($encuesta->version + 1),
                'descripcion' => $encuesta->descripcion,
                'estado' => EstadoEncuesta::Borrador->value,
                'version' => $encuesta->version + 1,
            ]);
            $borrador->save();
            return response()->json($borrador, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Pasar una encuesta al estado de publicada (o piloto)   
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function publicar(Request $request, $encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId);
            if (!$encuesta) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }
            // $validator = Validator::make($request->all(), [
            //     'fecha_finalizacion' => 'required',
            // ]);
            // if ($validator->fails()) {
            //     return response()->json(['error' => $validator->errors()], 400);
            // }
            // Validación de fecha de finalización
            if ($request->filled('fecha_finalizacion')) {
                $fechaFinalizacion = strtotime($request->fecha_finalizacion);
                if ($fechaFinalizacion === false || $fechaFinalizacion < time()) {
                    return response()->json(['error' => 'La fecha de finalización no es válida'], 400);
                }
            }
            // Validación de fecha de publicación
            // pendiente (borrador)
            if ($request->filled('fecha_publicacion')) {
                $fechaPublicacion = strtotime($request->fecha_publicacion);
                if ($fechaPublicacion === false || $fechaPublicacion > time()) {
                    return response()->json(['error' => 'La fecha de publicación no es válida'], 400);
                }
            }
            //Composición de un URL amigable
            $slug = Str::slug($encuesta->titulo_encuesta) . '-' . $encuesta->version;
            $encuesta->update([
                'url' => url('/encuestas/publicada/' . $slug), //cambiar: http://localhost:5173/encuesta/publicada/titulo-encuesta-1
                'estado' => $request['estado'],
                'fecha_publicacion' => $request->fecha_publicacion ?? null,
                'fecha_finalizacion' => $request->fecha_finalizacion ?? null,
                'limite_respuestas' => $request->limite_respuestas ?? null,
                'es_privada' => $request->es_privada,
                'es_anonima' => $request->es_anonima,
            ]);
            return response()->json($encuesta, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Marca una encuesta como finalizada.
     *
     * @param  \App\Models\Encuesta  $encuesta
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizar($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }
            $encuesta->fecha_finalizacion = now();
            $encuesta->save();

            return response()->json(['success' => 'Encuesta marcada como finalizada correctamente'], 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }
}
