<?php

namespace App\Http\Controllers;

use App\Mail\CompartirUrlEncuestaMailable;
use App\Models\Encuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MailController extends Controller
{
    /**
     * Envía correos a múltiples encuestados 
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function enviar(Request $request, $encuestaId)
    {
        try {
            // Trae la datos de la encuesta publicada
            $encuesta = Encuesta::where('id', $encuestaId)
                ->select('titulo_encuesta', 'descripcion', 'url', 'fecha_finalizacion')
                ->first();
            if (empty($encuesta->url)) {
                return response()->json(['error' => 'Url inexistente'], 404);
            }
            // Validar direcciones de correo
            $direccionesCorreos = $request->all();
            $errores = [];

            foreach ($direccionesCorreos as $direccionCorreo) {
                $validator = Validator::make(['email' => $direccionCorreo], [
                    'email' => 'required|email',
                ]);

                if ($validator->fails()) {
                    $errores[] = 'Dirección de correo no válida: ' . $direccionCorreo;
                } else {
                    try {
                        Mail::to($direccionCorreo)
                            ->send(new CompartirUrlEncuestaMailable($encuesta));
                    } catch (\Throwable $e) {
                        $errores[] = 'Error al enviar a: ' . $direccionCorreo . ' - ' . $e->getMessage();
                    }
                }
            }
            if (!empty($errores)) {
                return response()->json([
                    'success' => 'Correos enviados parcialmente.',
                    'errores' => $errores
                ], 206); // Status 206 Partial Content
            }
            return response()->json(['success' => 'Todos los correos enviados exitosamente'], 200);
            
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
