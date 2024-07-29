<?php

namespace App\Http\Controllers;

use App\Enums\TipoPregunta;
use App\Models\Encuesta;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class InformeController extends Controller
{
    /**
     * Display the specified resource.
     * 
     * @param  Request  $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $encuestaId)//, $email = null)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion', 'es_privada']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }
            if ($encuesta->es_privada) {
                // $email = $request->input('correo');
                if (!Auth::check() && (!$request->has('correo') || !$encuesta->esMiembro($request->input('correo')))) {
                    return response()->json(['code' => 'ENCUESTA_PRIVADA', 'message' => 'No tiene acceso a esta encuesta privada.'], 403);
                }
            }
            $informe = $this->generarInforme($encuesta);
            return response()->json($informe, 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Generar y descargar informe en formato .csv
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function downloadCsv($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion', 'es_privada']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }

            $informe = $this->generarInforme($encuesta);

            $callback = function () use ($informe) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Titulo Encuesta', $informe['titulo_encuesta']]);
                fputcsv($file, ['Dias Restantes', $informe['dias_restantes']]);
                fputcsv($file, []);

                foreach ($informe['preguntas'] as $pregunta) {
                    fputcsv($file, [$pregunta['titulo_pregunta']]);
                    fputcsv($file, ['Opcion', 'Resultados', 'Porcentaje']);
                    foreach ($pregunta['resultados'] as $resultado) {
                        fputcsv($file, [$resultado['titulo_opcion'], $resultado['resultado_opcion'], $resultado['porcentaje']]);
                    }
                    fputcsv($file, []);
                }
                fclose($file);
            };

            $filename = 'informe_' . $encuestaId . '.csv';
            return Response::streamDownload($callback, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Generar y descargar informe en formato pdf
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion', 'es_privada']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }

            $informe = $this->generarInforme($encuesta);

            $pdf = Pdf::loadView('informe', ['informe' => $informe]);
            $filename = 'informe_' . $encuestaId . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    private function generarInforme($encuesta)
    {
        $resultado = $encuesta->dias_restantes();
        $diasRestantes = is_null($resultado)
            ? 'Ya ha finalizado'
            : (string) $encuesta->dias_restantes();

        $informe = [
            'titulo_encuesta' => $encuesta->titulo_encuesta,
            'dias_restantes' => $diasRestantes,
            'preguntas' => []
        ];

        $preguntas = Pregunta::where('encuesta_id', $encuesta->id)->orderBy('id_orden')->get();

        foreach ($preguntas as $pregunta) {
            $resultados = [];
            switch ($pregunta->tipo_pregunta->value) {
                case TipoPregunta::Multiple->value:
                case TipoPregunta::Unique->value:
                case TipoPregunta::List->value:
                    $resultados = $this->seleccionResultados($pregunta);
                    break;
                case TipoPregunta::Rating->value:
                    $resultados = $this->puntuacionResultados($pregunta);
                    break;
                case TipoPregunta::Numeric->value:
                    $resultados = $this->numericoResultados($pregunta);
                    break;
                case TipoPregunta::Text->value:
                    $resultados = $this->textoResultados($pregunta);
                    break;
            }
            $informe['preguntas'][] = $resultados;
        }
        return $informe;
    }

    private function seleccionResultados(Pregunta $pregunta): array
    {
        try {
            $n = count($pregunta->seleccion);
            $resultados = array_fill(0, $n, 0);
            $respuestasEnBlanco = 0;
            $totalRespuestas = 0;
            $totalMrcadas = 0;

            $respuestas = $pregunta->respuestas()->get('seleccion');

            foreach ($respuestas as $respuesta) {
                $totalRespuestas++;
                if (is_null($respuesta->seleccion) || empty($respuesta->seleccion)) {
                    $totalMrcadas++;
                    $respuestasEnBlanco++;
                } else {
                    foreach ($respuesta->seleccion as $opcion) {
                        if (is_int($opcion) && $opcion >= 0 && $opcion < $n) {
                            $totalMrcadas++;
                            $resultados[$opcion]++;
                        }
                    }
                }
            }
            $resultadosFormateados = [];
            foreach ($pregunta->seleccion as $index => $tituloOpcion) {
                $porcentaje = $totalMrcadas > 0 ? ($resultados[$index] / $totalMrcadas) * 100 : 0;
                $resultadosFormateados[] = [
                    'titulo_opcion' => $tituloOpcion,
                    'resultado_opcion' => $resultados[$index],
                    'porcentaje' => round($porcentaje, 2)
                ];
            }
            $porcentajeBlanco = $totalRespuestas > 0 ? ($respuestasEnBlanco / $totalMrcadas) * 100 : 0;
            $resultadosFormateados[] = [
                'titulo_opcion' => 'sin responder',
                'resultado_opcion' => $respuestasEnBlanco,
                'porcentaje' => round($porcentajeBlanco, 2)
            ];

            return [
                'titulo_pregunta' => $pregunta->titulo_pregunta,
                'total_respuestas' => $totalRespuestas,
                'resultados' => $resultadosFormateados
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function puntuacionResultados(Pregunta $pregunta): array
    {
        [$min, $max, $step] = $pregunta->rango_puntuacion;

        // var_dump(json_encode([$min, $step, $max]));

        $resultados = [];
        $respuestasEnBlanco = 0;
        $totalRespuestas = 0;
        $sumaPuntuaciones = 0;
        $totalPromedio = 0;

        $respuestas = $pregunta->respuestas()->get(['puntuacion']);

        foreach ($respuestas as $respuesta) {
            $totalRespuestas++;
            if (is_null($respuesta->puntuacion)) {
                $respuestasEnBlanco++;
            } else {
                $valor = $respuesta->puntuacion;
                if (!isset($resultados[$valor])) {
                    $resultados[$valor] = 0;
                }
                $resultados[$valor]++;
                $sumaPuntuaciones += $valor;
            }
        }
        $resultadosFormateados = [];
        for ($i = $min; $i <= $max; $i += $step) {
            if (isset($resultados[$i]) && $resultados[$i] > 0) {
                $porcentaje = $totalRespuestas > 0 ? ($resultados[$i] / $totalRespuestas) * 100 : 0;
                $resultadosFormateados[] = [
                    'titulo_opcion' => $i,
                    'resultado_opcion' => $resultados[$i],
                    'porcentaje' => round($porcentaje, 2)
                ];
            }
        }
        $porcentajeBlanco = $totalRespuestas > 0 ? ($respuestasEnBlanco / $totalRespuestas) * 100 : 0;
        $resultadosFormateados[] = [
            'titulo_opcion' => 'sin responder',
            'resultado_opcion' => $respuestasEnBlanco,
            'porcentaje' => round($porcentajeBlanco, 2)
        ];

        $totalPromedio = $totalRespuestas > 0 ? $sumaPuntuaciones / ($totalRespuestas - $respuestasEnBlanco) : 0;

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'total_respuestas' => $totalRespuestas,
            'total_promedio' => round($totalPromedio, 2),
            'resultados' => $resultadosFormateados
        ];
    }

    private function numericoResultados(Pregunta $pregunta): array
    {

        // var_dump(json_encode([$min, $step, $max]));

        $respuestasEnBlanco = 0;
        $totalRespuestas = 0;
        $numeroNoNulos = 0;
        $sumaTotal = 0.00;
        $promedio = 0;

        // $respuestas = $pregunta->respuestas()->get(['valor_numerico']);
        $valoresNumericos = $pregunta->respuestas()->pluck('valor_numerico')->toArray();

        if (empty($valoresNumericos)) {
            return [
                'titulo_pregunta' => $pregunta->titulo_pregunta,
                'total_respuestas' => 0,
                'total_promedio' => 0,
                'resultados' => []
            ];
        }

        $totalRespuestas = count($valoresNumericos);

        $valoresNoNulos = array_filter($valoresNumericos, function ($valor) {
            return !is_null($valor);
        });
        $numeroNoNulos = count($valoresNoNulos);
        $sumaTotal = array_sum($valoresNoNulos);
        $respuestasEnBlanco = $totalRespuestas - $numeroNoNulos;
        $max = max($valoresNoNulos);
        $min = min($valoresNoNulos);
        $promedio = $numeroNoNulos > 0 ? $sumaTotal / $numeroNoNulos : 0;

        // Varianza (?)
        $media = $promedio;
        $sumatoriaDesviacionCuadrada = array_sum(array_map(function ($valor) use ($media) {
            return pow($valor - $media, 2);
        }, $valoresNoNulos));
        $varianza = $numeroNoNulos > 0 ? $sumatoriaDesviacionCuadrada / $numeroNoNulos : 0;
        $varianza = round($varianza, 2);
        
        
        

        
        // Desviación estándar (?)
        $desviacionEstandar = round(sqrt($varianza), 2);

        // Dividir el rango en 5 intervalos
        $intervalo = ($max - $min) / 5;
        $contadores = array_fill(0, 5, 0);

        foreach ($valoresNoNulos as $valor) {
            if ($valor == $max) {
                $contadores[4]++;
            } else {
                $index = floor(($valor - $min) / $intervalo);
                $contadores[$index]++;
            }
        }

        $resultadosFormateados = [];

        for ($i = 0; $i < 5; $i++) {
            $tramoInicio = $min + $i * $intervalo;
            $tramoFin = $min + ($i + 1) * $intervalo;
            $porcentaje = $totalRespuestas > 0 ? ($contadores[$i] / $totalRespuestas) * 100 : 0;
            $resultadosFormateados[] = [
                'titulo_opcion' => '[' . $tramoInicio . ' - ' . $tramoFin . ')',
                'resultado_opcion' => $contadores[$i],
                'porcentaje' => round($porcentaje, 2)
            ];
        }

        $porcentajeBlanco = $totalRespuestas > 0 ? ($respuestasEnBlanco / $totalRespuestas) * 100 : 0;
        $resultadosFormateados[] = [
            'titulo_opcion' => 'sin responder',
            'resultado_opcion' => $respuestasEnBlanco,
            'porcentaje' => round($porcentajeBlanco, 2)
        ];

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'total_respuestas' => $totalRespuestas,
            'total_promedio' => round($promedio, 2),
            'valor_maximo' => $max,
            'valor_minimo' => $min,
            'varianza' => $varianza,
            'desviacion_estandar' => $desviacionEstandar,
            'resultados' => $resultadosFormateados
        ];
    }


    private function textoResultados(Pregunta $pregunta): array
    {
        $respuestasEnBlanco = 0;
        $totalRespuestas = 0;
        $sumaTotalPalabras = 0;
        $totalPromedio = 0;
        $resultados = [
            'respuesta_corta' => 0,
            'respuesta_normal' => 0,
            'respuesta_larga' => 0
        ];

        $respuestas = $pregunta->respuestas()->get(['entrada_texto']);

        foreach ($respuestas as $respuesta) {
            $totalRespuestas++;
            if (is_null($respuesta->entrada_texto) || empty($respuesta->entrada_texto)) {
                $respuestasEnBlanco++;
            } else {
                $numPalabras = str_word_count($respuesta->entrada_texto);
                $sumaTotalPalabras += $numPalabras;
                if ($numPalabras < 15) {
                    $resultados['respuesta_corta']++;
                } elseif ($numPalabras <= 50) {
                    $resultados['respuesta_normal']++;
                } else {
                    $resultados['respuesta_larga']++;
                }
            }
        }
        $resultadosFormateados = [];
        foreach ($resultados as $tipoRespuesta => $cantidad) {
            $porcentaje = $totalRespuestas > 0 ? ($cantidad / ($totalRespuestas - $respuestasEnBlanco)) * 100 : 0;
            $resultadosFormateados[] = [
                'titulo_opcion' => $tipoRespuesta,
                'resultado_opcion' => $cantidad,
                'porcentaje' => round($porcentaje, 2)
            ];
        }
        $porcentajeBlanco = $totalRespuestas > 0 ? ($respuestasEnBlanco / $totalRespuestas) * 100 : 0;
        $resultadosFormateados[] = [
            'titulo_opcion' => 'sin responder',
            'resultado_opcion' => $respuestasEnBlanco,
            'porcentaje' => round($porcentajeBlanco, 2)
        ];

        $totalPromedio = $totalRespuestas > 0 ? $sumaTotalPalabras / ($totalRespuestas - $respuestasEnBlanco) : 0;

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'total_respuestas' => $totalRespuestas,
            'total_promedio' => round($totalPromedio, 2),
            'resultados' => $resultadosFormateados
        ];
    }

    /**
     * Generar y descargar ENCUESTAS en formato pdf (para imprimir) 
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function downloadSurveyPdf($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'descripcion']);
            if (!$encuesta) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }

            $preguntas = Pregunta::where('encuesta_id', $encuestaId)->orderBy('id_orden')->get();

            $pdf = Pdf::loadView('encuesta', compact('encuesta', 'preguntas'));
            $filename = 'encuesta_' . $encuestaId . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
