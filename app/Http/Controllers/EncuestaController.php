<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEncuesta;
use App\Models\Encuesta;
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
        $validator = Validator::make($request->all(), [
            'titulo_encuesta' => 'required|string|max:100|unique:encuestas,titulo_encuesta',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        try {
            $encuesta = Encuesta::create($request->all());
            return response()->json($encuesta, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Muestra una encuesta a partir de la url amigable (slug)
     * 
     * @param  string  $slug
     */
    public function show($slug)
    {
        try {
            $arraySlug = explode('-', $slug); 
            $version = end($arraySlug);
            $encuesta = Encuesta::where('url', 'like', "%{$slug}%")->where('version', $version)->first();
            if (!$encuesta) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }
            return response()->json($encuesta, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
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
        if ($encuesta->estado === EstadoEncuesta::Borrador->value) {
            return response()->json($encuesta, 200);
        } else {
            return response()->json(['error' => 'No se puede editar la encuesta. No es "Borrador".'], 403);
        }
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
                'url' => url('/encuestas/publicada/' . $slug),
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
}
