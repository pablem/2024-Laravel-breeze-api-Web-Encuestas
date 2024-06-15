<?php

namespace App\Http\Controllers;

use App\Models\Encuestado;
use App\Models\MiembroEncuestaPrivada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MiembroEncuestaPrivadaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //sin uso
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //sin uso
    }

    /**
     * Registra multiples miembros en un grupo de encuestados privados
     * (Con id=null crea un nuevo encuestado)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $encuestaId
     * 
     */
    public function store(Request $request, $encuestaId)
    {
        try {
            $validator = Validator::make($request->all(), [
                '*.id' => 'nullable',
                '*.correo' => 'required|email|unique:encuestados,correo',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            DB::beginTransaction();
            foreach ($request->json()->all() as $encuestadoData) {
                //     $validator = Validator::make($encuestadoId, [
                //         //
                //     ]);
                // if ($validator->fails()) {
                //     return response()->json(['error' => $validator->errors()], 400);
                // }
                if (isset($encuestadoData['id']) && $encuestadoData['id']) {
                    $encuestadoId = $encuestadoData['id'];

                    $exists = MiembroEncuestaPrivada::where('encuesta_id', $encuestaId)
                    ->where('encuestado_id', $encuestadoId)
                    ->exists();

                    if ($exists) {
                        return response()->json(['error' => "El encuestado ya se agregó al grupo privado"], 400);
                    }

                } else {
                    // Si el encuestado no tiene id, verificar si el correo existe
                    $encuestado = Encuestado::where('correo', $encuestadoData['correo'])->first();
                    if (!$encuestado) {
                        // Crear nuevo encuestado
                        $encuestado = new Encuestado([
                            'correo' => $encuestadoData['correo']
                        ]);
                        $encuestado->save();
                    }
                    $encuestadoId = $encuestado->id;
                }
                
                $nuevoMiembro = new MiembroEncuestaPrivada([
                    'encuesta_id' => $encuestaId,
                    'encuestado_id' => $encuestadoId
                ]);
                $nuevoMiembro->save();
            }

            DB::commit();
            return response()->json(['success' => 'Se guardaron los destinatarios de esta encuesta privada'], 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MiembroEncuestaPrivada $miembroEncuestaPrivada)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MiembroEncuestaPrivada $miembroEncuestaPrivada)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MiembroEncuestaPrivada $miembroEncuestaPrivada)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MiembroEncuestaPrivada $miembroEncuestaPrivada)
    {
        //
    }
}
