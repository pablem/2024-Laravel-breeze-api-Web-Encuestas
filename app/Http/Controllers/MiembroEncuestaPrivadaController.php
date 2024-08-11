<?php

namespace App\Http\Controllers;

use App\Models\Encuestado;
use App\Models\MiembroEncuestaPrivada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiembroEncuestaPrivadaController extends Controller
{
    /**
     * Display the specified resource.
     * @param  int $encuestaId
     */
    public function getMiembrosIds($encuestaId)
    {
        try {
            $miembros = MiembroEncuestaPrivada::where('encuesta_id', $encuestaId)->pluck('encuestado_id')->toArray();
            return response()->json($miembros, 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Registra multiples miembros en un grupo de encuestados privados
     * (Con id=null crea un nuevo encuestado)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $encuestaId
     */
    public function store(Request $request, $encuestaId)
    {
        try {
            $contadorNuevosMiembros = 0;
            DB::beginTransaction();
            foreach ($request->json()->all() as $encuestadoData) {

                $encuestadoId = isset($encuestadoData['id'])
                    ? $encuestadoData['id']
                    : Encuestado::where('correo', $encuestadoData['correo'])->pluck('id')->first();
                if (!$encuestadoId) {
                    continue;
                }
                $exists = MiembroEncuestaPrivada::where('encuesta_id', $encuestaId)
                    ->where('encuestado_id', $encuestadoId)
                    ->exists();
                if (!$exists) {
                    $nuevoMiembro = new MiembroEncuestaPrivada([
                        'encuesta_id' => $encuestaId,
                        'encuestado_id' => $encuestadoId
                    ]);
                    if ($nuevoMiembro->save()) {
                        $contadorNuevosMiembros++;
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Miembros privados agregados: ' . $contadorNuevosMiembros], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $encuestaId
     */
    public function destroy(Request $request, $encuestaId)
    {
        try {
            DB::beginTransaction();
            $ids = $request->json()->all();
            $deleted = MiembroEncuestaPrivada::where('encuesta_id', $encuestaId)->whereIn('encuestado_id', $ids)->delete();
            DB::commit();
            return response()->json(['message' => 'Miembros privados removidos: ' . $deleted], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
}
