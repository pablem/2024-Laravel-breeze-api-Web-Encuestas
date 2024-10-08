<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEncuesta;
use App\Enums\TipoPregunta;
use App\Mail\CompartirUrlEncuestaMailable;
use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\Feedback_encuesta;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EncuestaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $encuestas = Encuesta::with('user:id,name')
            ->select('id', 'titulo_encuesta', 'estado', 'es_anonima', 'es_privada', 'fecha_finalizacion', 'user_id', 'created_at', 'updated_at')
            ->addSelect([
                'tiene_mensajes' => Feedback_encuesta::selectRaw('COUNT(*) > 0')
                    ->whereColumn('encuesta_id', 'encuestas.id')
            ])
            ->orderBy('id', 'desc')
            ->get();

        foreach ($encuestas as $encuesta) {
            $encuesta->es_finalizada = $encuesta->esFinalizada();
        }
        return response()->json($encuestas, 200);
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
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $encuesta = Encuesta::create($request->all());
            return response()->json($encuesta, 201);
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
        // if ($encuesta->estado !== EstadoEncuesta::Borrador) {
        //     return response()->json(['error' => 'No se puede editar la encuesta. No es "Borrador".'], 400);
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
        $validator = Validator::make($request->all(), ['titulo_encuesta' => ['required', 'string', 'max:100', Rule::unique('encuestas')->ignore($encuestaId)],]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
        DB::beginTransaction();
        try {
            $encuesta = Encuesta::find($encuestaId);
            if (!$encuesta) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }
            $ultimaVersion = $encuesta->ultimaVersion();
            $borrador = new Encuesta([
                'user_id' => Auth::user()->id,
                'id_versionamiento' => $encuesta->id_versionamiento,
                'titulo_encuesta' => $encuesta->titulo_encuesta . ' (' . ($ultimaVersion + 1) . ')',
                'descripcion' => $encuesta->descripcion,
                'estado' => EstadoEncuesta::Borrador->value,
                'version' => $ultimaVersion + 1,
            ]);
            $borrador->save();

            $preguntas = Pregunta::where('encuesta_id', $encuestaId)->get();
            if ($preguntas->isNotEmpty()) {
                foreach ($preguntas as $pregunta) {
                    $nuevaPregunta = $pregunta->replicate();
                    $nuevaPregunta->encuesta_id = $borrador->id;
                    $nuevaPregunta->save();
                }
            }
            DB::commit();
            return response()->json($borrador, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Cambiar propiedades de publicación (Todo tipo de encuestas) 
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
            // Construcción dinámica de las reglas de validación
            $rules = [
                'fecha_publicacion' => ['nullable', 'date', 'before_or_equal:now'],
                'limite_respuestas' => ['integer', 'gte:0'],
            ];
            if ($encuesta->estado === EstadoEncuesta::Borrador) {
                $rules['fecha_finalizacion'] = ['nullable', 'date', 'after:now'];
            } else {
                $rules['fecha_finalizacion'] = ['nullable', 'date'];
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            //Composición de un URL amigable
            $slug = Str::slug($encuesta->titulo_encuesta) . '-' . $encuesta->version;
            $encuesta->update([
                'url' => config('app.frontend_url') . '/encuesta/publicada/' . $slug, //local: http://localhost:5173/encuesta/publicada/titulo-encuesta-1
                'estado' => $request['estado'],
                'fecha_publicacion' => $encuesta->estado === EstadoEncuesta::Borrador ? now()->toDateString() : $request->fecha_publicacion ?? null,
                'fecha_finalizacion' => $request->fecha_finalizacion ?? null,
                'limite_respuestas' => $request->limite_respuestas ?? null,
                'es_privada' => $request->es_privada,
                'es_anonima' => $request->es_anonima,
            ]);
            return response()->json(['message' => 'Encuesta Publicada: Se guardaron los cambios.'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
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
            $encuesta->fecha_finalizacion = now()->toDateString();
            $encuesta->save();

            return response()->json(['message' => 'Encuesta marcada como finalizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Obtiene todos los comentarios o feedback de una encuesta piloto 
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function getFeedbacks($encuestaId)
    {
        $encuesta = Encuesta::select('id', 'titulo_encuesta', 'estado')->find($encuestaId);
        if (!$encuesta || $encuesta->estado !== EstadoEncuesta::Piloto) {
            return response()->json(['error' => 'Encuesta no encontrada o no es piloto'], 404);
        }

        $entradas = Feedback_encuesta::select('id', 'comentarios as entrada_texto', 'created_at')
            ->where('encuesta_id', $encuestaId)
            ->whereNotNull('comentarios')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();
        $feedbacks = [
            'titulo' => $encuesta->titulo_encuesta,
            'entradas' => $entradas
        ];

        return response()->json($feedbacks, 200);
    }

    public function getTextResponse($preguntaId)
    {
        $pregunta = Pregunta::select('id', 'titulo_pregunta', 'tipo_pregunta')->find($preguntaId);
        if (!$pregunta || $pregunta->tipo_pregunta !== TipoPregunta::Text) {
            return response()->json(['error' => 'Pregunta no encontrada o no es del tipo respuesta de texto.'], 404);
        }

        $entradas = Respuesta::select('id', 'entrada_texto', 'created_at')
            ->where('pregunta_id', $preguntaId)
            ->whereNotNull('entrada_texto')
            ->orderBy('created_at')
            // ->limit(100)
            ->get();
        $respuestas = [
            'titulo' => $pregunta->titulo_pregunta,
            'entradas' => $entradas
        ];

        return response()->json($respuestas, 200);
    }

    /**
     * Muestra una encuesta a partir de la url amigable (slug)
     * --ENLACE COLECTIVO--
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     */
    public function showByCollectiveLink($slug, Request $request)
    {
        try {
            // Obtener título y dirección de la URL
            $encuesta = $this->obtenerEncuesta($slug);
            if (!$encuesta) {
                return response()->json(['code' => 'ENCUESTA_NO_ENCONTRADA', 'message' => 'Encuesta no encontrada'], 404);
            }
            //Verificación de encuesta: Está finalizada? - limite de respuestas alcanzado? 
            $verificacion1 = $this->verificarEncuesta($encuesta);
            if ($verificacion1) {
                return $verificacion1;
            }
            if ($encuesta->es_anonima) {
                //ANONIMA El encuestado ya respondió?
                $ip = $request->ip();
                $respuestaExistente = $this->verificarRespuestaExistente($encuesta, ['ip' => $ip]);
                if ($respuestaExistente) {
                    return $respuestaExistente;
                }
                //ANONIMA Pasó las verificaciones:
                return response()->json(['code' => 'ENCUESTA_DISPONIBLE', 'encuesta' => $encuesta], 200);
            } else {
                //NO ANONIMA - se requiere correo
                if (!$request->has('correo')) {
                    return response()->json(['code' => 'ENCUESTA_NO_ANONIMA', 'message' => 'Encuesta no anónima. Debe identificarse con un correo electrónico válido.'], 200);
                }
                //NO ANONIMA Verificación de encuesta con correo: correo valido? - ya respondió? - no pertenece a grupo privado?  
                $correo = $request->input('correo');
                $verificacion2 = $this->verificarCorreo($encuesta, $correo);
                if ($verificacion2) {
                    return $verificacion2;
                }
                //No ANONIMA Pasó las verificaciones. Se registra el correo y se envía invitación para responder desde correo verificado
                return $this->nuevoEncuestadoEnviar($encuesta, $correo);
            }
        } catch (\Throwable $th) {
            return response()->json(['code' => 'ERROR_SERVIDOR', 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Muestra una encuesta a partir de la url amigable (slug)
     * --ENLACE INDIVIDUAL--
     * 
     * @param  id  $encuestadoId
     * @param  string  $slug
     * @param  string  $hash
     */
    public function showByIndividualLink($slug, $encuestadoId, $hash)
    {
        try {
            $correo = Encuestado::where('id', $encuestadoId)->pluck('correo')->first();
            if (!$correo || !hash_equals(sha1($correo), (string) $hash)) {
                return response()->json(['code' => 'EMAIL_NO_VERIFICADO', 'message' => 'Falló la verificación del correo del encuestado.'], 200); //400
            }

            /* El encuestado accedió desde el correo: Se actualiza su validación */
            Encuestado::where('correo', $correo)
                ->whereNull('validacion')
                ->update(['validacion' => 0]);

            $encuesta = $this->obtenerEncuesta($slug);
            if (!$encuesta) {
                return response()->json(['code' => 'ENCUESTA_NO_ENCONTRADA', 'message' => 'Encuesta no encontrada'], 404); //404
            }
            $verificacion1 = $this->verificarEncuesta($encuesta);
            if ($verificacion1) {
                return $verificacion1;
            }
            $verificacion2 = $this->verificarCorreo($encuesta, $correo);
            return $verificacion2 ? $verificacion2 : response()->json(['code' => 'ENCUESTA_DISPONIBLE', 'encuesta' => $encuesta, 'correo' => $correo], 200);
        } catch (\Throwable $th) {
            return response()->json(['code' => 'ERROR_SERVIDOR', 'message' => $th->getMessage()], 500);
        }
    }
    /************ VERIFICACIONES */
    /**
     * Obtener encuesta 
     * @param  string  $slug
     * @return Encuesta|null
     */
    private function obtenerEncuesta($slug)
    {
        $arraySlug = explode('-', $slug);
        $version = end($arraySlug);
        return Encuesta::where('url', 'like', "%{$slug}%")->where('version', $version)->first();
    }
    /**
     * Verifica encuesta: 
     * fecha finalización - límite respuestas - encuesta privada - no anónima ya respondida
     * 
     * @param  Encuesta  $encuesta
     * @param string $correo
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function verificarEncuesta($encuesta)
    {
        if ($encuesta->esFinalizada()) {
            return response()->json(['code' => 'ENCUESTA_FINALIZADA', 'id' => $encuesta->id, 'message' => 'Encuesta finalizada'], 200);
        }
        if ($encuesta->limite_respuestas > 0 && $encuesta->numeroRespuestas() >= $encuesta->limite_respuestas) {
            return response()->json(['code' => 'LIMITE_RESPUESTAS_ALCANZADO', 'id' => $encuesta->id, 'message' => 'Se ha alcanzado el límite de respuestas para esta encuesta.'], 200);
        }
        return null;
    }

    /**
     * Verifica correo: 
     * encuesta privada - no anónima ya respondida
     * 
     * @param  Encuesta  $encuesta
     * @param string $correo
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function verificarCorreo($encuesta, $correo)
    {
        $validator = Validator::make(['correo' => $correo], [
            'correo' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => 'EMAIL_INVALIDO', 'message' => $validator->errors()->first('correo')], 422);
        }
        if ($encuesta->es_privada && !$encuesta->esMiembro($correo)) {
            return response()->json(['code' => 'ENCUESTA_PRIVADA', 'message' => 'Esta encuesta es privada. Ud. no está autorizado para responder.'], 200); //403
        }
        $respuestaExistente = $this->verificarRespuestaExistente($encuesta, ['correo' => $correo]);
        if ($respuestaExistente) {
            return $respuestaExistente;
        }
        return null;
    }
    /**
     * Verifica si una respuesta ya existe para una anónima o no anónima.
     *
     * @param  Encuesta  $encuesta
     * @param  array  $identificador
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function verificarRespuestaExistente($encuesta, $identificador)
    {
        $query = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
            ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
            ->where('preguntas.encuesta_id', $encuesta->id);

        if (isset($identificador['ip'])) {
            $query->where('encuestados.ip_identificador', $identificador['ip']);
        } else if (isset($identificador['correo'])) {
            $query->where('encuestados.correo', $identificador['correo']);
        }

        $respuestaExistente = $query->first(['respuestas.*']);
        if ($respuestaExistente) {
            return response()->json(['code' => 'ENCUESTA_YA_RESPONDIDA', 'id' => $encuesta->id, 'message' => 'Ud. ya ha respondido esta encuesta.'], 200);
        }

        return null;
    }
    private function nuevoEncuestadoEnviar($encuesta, $correo)
    {
        try {
            // Recuperar encuestado / o Registrar nuevo encuestado
            $encuestado = Encuestado::where('correo', $correo)->first();

            if (!$encuestado) {
                $encuestado = new Encuestado([
                    'correo' => $correo
                ]);
                $encuestado->save();
            }
            // Enviar el correo
            Mail::to($encuestado->correo)
                ->send(new CompartirUrlEncuestaMailable($encuesta, $encuestado->id, $encuestado->correo));

            // Retornar respuesta exitosa
            return response()->json(['code' => 'NUEVO_ENCUESTADO', 'message' => 'Se envió la encuesta a su correo.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['code' => 'ERROR_NUEVO_ENCUESTADO', 'message' => $e->getMessage()], 500);
        }
    }
    /************ FIN VERIFICACIONES */

    // public function showByEmail($slug, Request $request)
    // {
    //     try {
    //         $correo = $request->input('correo'); //control de dominio correo
    //         $arraySlug = explode('-', $slug);
    //         $version = end($arraySlug);
    //         $encuesta = Encuesta::where('url', 'like', "%{$slug}%")->where('version', $version)->first();
    //         if (!$encuesta) {
    //             return response()->json(['code' => 'ENCUESTA_NO_ENCONTRADA', 'message' => 'Encuesta no encontrada'], 404);
    //         }
    //         if ($encuesta->esFinalizada()) {
    //             return response()->json(['code' => 'ENCUESTA_FINALIZADA', 'message' => 'Encuesta finalizada'], 200);
    //         }
    //         if ($encuesta->limite_respuestas > 0 && $encuesta->numeroRespuestas() >= $encuesta->limite_respuestas) {
    //             return response()->json(['code' => 'LIMITE_RESPUESTAS_ALCANZADO', 'message' => 'Se ha alcanzado el límite de respuestas para esta encuesta.'], 200);
    //         }
    //         if ($encuesta->es_anonima) {
    //             return response()->json(['code' => 'ENCUESTA_ANONIMA', 'message' => 'Esta encuesta es anónima. Ingresar sin correo.'], 200);
    //         }
    //         if ($encuesta->es_privada && !$encuesta->esMiembro($correo)) {
    //             // $esMiembro = MiembroEncuestaPrivada::join('encuestados', 'miembro_encuesta_privadas.encuestado_id', '=', 'encuestados.id')
    //             //     ->where('encuestados.correo', $correo)
    //             //     ->where('miembro_encuesta_privadas.encuesta_id', $encuesta->id)
    //             //     ->exists();
    //             // if (!$esMiembro) {
    //             return response()->json(['code' => 'ENCUESTA_PRIVADA', 'message' => 'Esta encuesta es privada. Ud. no está autorizado para responder.'], 403);
    //             // }
    //         }
    //         $respuestaExistente = Respuesta::join('preguntas', 'respuestas.pregunta_id', '=', 'preguntas.id')
    //             ->join('encuestados', 'respuestas.encuestado_id', '=', 'encuestados.id')
    //             ->where('preguntas.encuesta_id', $encuesta->id)
    //             ->where('encuestados.correo', $correo)
    //             ->first(['respuestas.*']);
    //         if ($respuestaExistente) {
    //             return response()->json(['code' => 'ENCUESTA_YA_RESPONDIDA', 'message' => 'Ud. ya ha respondido esta encuesta.'], 200);
    //         }
    //         return response()->json(['code' => 'ENCUESTA_DISPONIBLE', 'encuesta' => $encuesta], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json(['code' => 'ERROR_SERVIDOR', 'message' => $th->getMessage()], 500);
    //     }
    // }
}
