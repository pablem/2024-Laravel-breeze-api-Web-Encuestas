<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\MiembroEncuestaPrivada;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EncuestadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getEncuestadosConCorreo()
    {
        $encuestados = Encuestado::whereNotNull('correo')->get();
        return response()->json($encuestados, 200);
    }

    /**
     * Display a listing of the resource.
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function getEncuestadosSinResponder($encuestaId)
    {
        try {
            // Trae el atributo booleano es_privada de la encuesta
            $esPrivada = Encuesta::where('id', $encuestaId)->pluck('es_privada')->first();

            // Subconsulta para obtener los IDs de los encuestados que han respondido alguna pregunta de la encuesta
            $respondidosSubquery = Respuesta::select('encuestado_id')
                ->whereIn('pregunta_id', function ($query) use ($encuestaId) {
                    $query->select('id')
                        ->from('preguntas')
                        ->where('encuesta_id', $encuestaId);
                });

            // Construir la consulta para obtener los encuestados que no han respondido
            $query = Encuestado::whereNotNull('correo')
                ->whereNotIn('id', $respondidosSubquery);

            // Si la encuesta es privada, filtrar solo los miembros de la encuesta privada
            if ($esPrivada) {
                $miembrosSubquery = MiembroEncuestaPrivada::select('encuestado_id')
                    ->where('encuesta_id', $encuestaId);

                $query->whereIn('id', $miembrosSubquery);
            }

            $encuestadosSinResponder = $query->get();
            return response()->json($encuestadosSinResponder, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Lista todos los encuestados y marca con "check" los pertenecientes a una encuesta privada
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function getEncuestadosPrivados($encuestaId)
    {
        try {
            $encuesta = Encuesta::select('es_privada')->findOrFail($encuestaId);
            if (!$encuesta->es_privada) {
                return response()->json(['message' => 'La encuesta no es privada.'], 400);
            }

            $miembrosIds = MiembroEncuestaPrivada::where('encuesta_id', $encuestaId)
                ->pluck('encuestado_id')
                ->toArray();

            // Obtener todos los encuestados y agregar el campo "check"
            $encuestados = Encuestado::whereNotNull('correo')->get()->map(function ($encuestado) use ($miembrosIds) {
                $encuestado->check = in_array($encuestado->id, $miembrosIds) ? true : false;
                return $encuestado;
            });

            return response()->json($encuestados, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //sin uso
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $encuestadosData = $request->json()->all();
            $success = [];
            $errors = [];

            foreach ($encuestadosData as $data) {
                if (!isset($data['id'])) {
                    $validator = Validator::make($data, [
                        'correo' => 'required|email|unique:encuestados,correo',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = [
                            'correo' => $data['correo'],
                            'errors' => $validator->errors()
                        ];
                        continue;
                    }

                    $encuestado = new Encuestado([
                        'correo' => $data['correo'],
                        // 'ip_identificador' => null, //=> $request->ip(),
                    ]);
                    $encuestado->save();
                    $success[] = $encuestado;
                }
            }

            DB::commit();
            return response()->json(['guardados' => $success, 'errores' => $errors], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //sin uso
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //sin uso
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $encuestado = Encuestado::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'correo' => 'required|email|unique:encuestados,correo,' . $encuestado->id,
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $encuestado->correo = $request->correo;
            $encuestado->save();

            return response()->json(['message' => 'Encuestado actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage. (varios ids)
     */
    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();

            $ids = $request->json()->all();
            var_dump($ids);
            $deleted = Encuestado::whereIn('id', $ids)->delete();

            DB::commit();
            return response()->json(['message' => 'Encuestados eliminados correctamente', 'deleted' => $deleted], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
