<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEncuesta;
use App\Models\Encuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function createAndStore()
    {
        $encuesta = new Encuesta();
        $encuesta->save();
        //Hace falta un objeto json de preguntas vacío?
        return response()->json([], 204);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //      'titulo_encuesta' => 'required|string|max:40',
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(['error' => $validator->errors()], 400);
        // }
        try {
            $encuesta = Encuesta::create($request->all());
            return response()->json($encuesta, 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Encuesta $encuesta)
    {
        // sin uso 
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
            'titulo_encuesta' => 'required|string|max:40',
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
     * Remove the specified resource from storage.
     *
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function nuevaVersion($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId);
            $borrador = new Encuesta([
                'user_id' => $encuesta->user_id,// borrar a la hora de implementar el frontend  
                'id_versionamiento' => $encuesta->id_versionamiento,
                'titulo_encuesta' => $encuesta->titulo_encuesta,// . ' version ' . ($encuesta->version + 1),
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
}
