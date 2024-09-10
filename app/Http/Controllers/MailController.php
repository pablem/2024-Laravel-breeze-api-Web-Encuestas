<?php

namespace App\Http\Controllers;

use App\Mail\CompartirUrlEncuestaMailable;
use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\MiembroEncuestaPrivada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MailController extends Controller
{
    // /**
    //  * Envía correos a múltiples encuestados 
    //  * 
    //  * @param  \Illuminate\Http\Request $request
    //  * @param int $encuestaId
    //  * @return \Illuminate\Http\Response
    //  */
    // public function enviarCorreosAnonimos(Request $request, $encuestaId)
    // {
    //     try {
    //         // Trae la datos de la encuesta publicada
    //         $encuesta = Encuesta::where('id', $encuestaId)
    //             ->select('titulo_encuesta', 'descripcion', 'url', 'fecha_finalizacion')
    //             ->first();
    //         if (empty($encuesta->url)) {
    //             return response()->json(['error' => 'Url inexistente'], 404);
    //         }
    //         // Validar direcciones de correo
    //         $direccionesCorreos = $request->all();
    //         $errores = [];

    //         foreach ($direccionesCorreos as $direccionCorreo) {
    //             $validator = Validator::make(['email' => $direccionCorreo], [
    //                 'email' => 'required|email',
    //             ]);

    //             if ($validator->fails()) {
    //                 $errores[] = 'Dirección de correo no válida: ' . $direccionCorreo;
    //             } else {
    //                 try {
    //                     Mail::to($direccionCorreo)
    //                         ->send(new CompartirUrlEncuestaMailable($encuesta));
    //                 } catch (\Throwable $e) {
    //                     $errores[] = 'Error al enviar a: ' . $direccionCorreo . ' - ' . $e->getMessage();
    //                 }
    //             }
    //         }
    //         if (!empty($errores)) {
    //             return response()->json([
    //                 'success' => 'Correos enviados parcialmente.',
    //                 'errores' => $errores
    //             ], 206); // Status 206 Partial Content
    //         }
    //         return response()->json(['success' => 'Todos los correos enviados exitosamente'], 200);
    //     } catch (\Throwable $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * Envía correos a múltiples encuestados 
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function enviarCorreos(Request $request, $encuestaId)
    {
        try {
            // Trae la datos de la encuesta publicada
            $encuesta = Encuesta::where('id', $encuestaId)
                ->select('titulo_encuesta', 'descripcion', 'url', 'fecha_finalizacion', 'es_anonima', 'es_privada')
                ->first();
            if (empty($encuesta->url)) {
                return response()->json(['message' => 'Url inexistente'], 400);
            }
            $encuestados = $request->json()->all();
            if (empty($encuestados) || !is_array($encuestados)) {
                return response()->json(['message' => 'No se proporcionaron encuestados válidos'], 400);
            }
            $errores = '';
            foreach ($encuestados as $encuestado) {
                $validator = Validator::make($encuestado, [
                    'correo' => 'required|email',
                ]);
                if ($validator->fails()) {
                    $errores .= $encuestado['correo'] . ': ' . implode(', ', $validator->errors()->all()) . "\n";
                    continue;            
                }
                if (!isset($encuestado['id']) && !$encuesta->es_anonima) {
                    $validator = Validator::make($encuestado, [
                        'correo' => 'unique:encuestados,correo',
                    ]);
                    if ($validator->fails()) {
                        $errores .= $encuestado['correo'] . ': ' . implode(', ', $validator->errors()->all()) . "\n";
                        continue;            
                    }
                    $encuestado = new Encuestado([
                        'correo' => $encuestado['correo'],
                        // 'ip_identificador' => null, //=> $request->ip(),
                    ]);
                    $encuestado->save();
                    if($encuesta->es_privada) {
                        $miembro = new MiembroEncuestaPrivada([
                            'encuesta_id' => $encuestaId,
                            'encuestado_id' => $encuestado->id,
                        ]);
                        $miembro->save();
                    }
                }
                try {
                    if($encuesta->es_anonima) {
                        Mail::to($encuestado['correo'])
                            ->send(new CompartirUrlEncuestaMailable($encuesta));
                    } else {
                        Mail::to($encuestado['correo'])
                        ->send(new CompartirUrlEncuestaMailable($encuesta,$encuestado['id'],$encuestado['correo'] ));
                    }
                } catch (\Throwable $e) {
                    $errores .= $encuestado['correo'] . ': ' . $e->getMessage() . "\n";
                    continue;
                }
            }
            DB::commit();
            if (!empty($errores)) {
                return response()->json([
                    // 'message' => 'Correos enviados parcialmente.',
                    'message' => $errores
                ], 400); // Status 206 Partial Content
            }
            return response()->json(['message' => 'Se enviaron todos los correos exitosamente'], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
