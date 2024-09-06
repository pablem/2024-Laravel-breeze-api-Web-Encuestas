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
use HiFolks\Statistics\Stat;
use HiFolks\Statistics\Freq;
// use phpDocumentor\Reflection\Types\This;

class InformeController extends Controller
{
    /**
     * Display the specified resource.
     * 
     * @param  Request  $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $encuestaId) //, $email = null)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion', 'es_privada']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }
            if ($encuesta->es_privada) {
                // $email = $request->input('correo');
                if (!Auth::check() && (!$request->has('correo') || !$encuesta->esMiembro($request->input('correo')))) {
                    return response()->json(['code' => 'ENCUESTA_PRIVADA', 'message' => 'No tiene acceso a esta encuesta privada.'], 200); //403
                }
            }
            $informe = $this->generarInforme($encuesta);
            return response()->json($informe, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /* Funciones adicionales propuestas : extraer a csv todos los datos de los tipos de preguntas: texto y valor numérico  
        (ya que en el informe csv se presentan los datos condensados en rangos) */

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
                fputcsv($file, ['Numero de Respuestas', $informe['numero_respuestas']]);
                fputcsv($file, []);

                foreach ($informe['preguntas'] as $pregunta) {
                    fputcsv($file, ['Titulo', $pregunta['titulo_pregunta']]);
                    fputcsv($file, ['Tipo', $pregunta['tipo_pregunta']]);
                    fputcsv($file, ['Opcion', 'Resultados', 'Porcentaje']);
                    foreach ($pregunta['resultados'] as $resultado) {
                        fputcsv($file, [$resultado['titulo_opcion'], $resultado['resultado_opcion'], $resultado['porcentaje']]);
                    }
                    // Estadísticas
                    foreach ($pregunta['estadisticas'] as $key => $value) {
                        if (is_array($value)) {
                            fputcsv($file, [ucfirst(str_replace('_', ' ', $key))]);
                            foreach ($value as $subKey => $subValue) {
                                fputcsv($file, [
                                    ucfirst(str_replace('_', ' ', $subKey)),
                                    $subValue
                                ]);
                            }
                        } else {
                            fputcsv($file, [
                                ucfirst(str_replace('_', ' ', $key)),
                                $value ?? 'N/A'
                            ]);
                        }
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
        try {
            $resultado = $encuesta->diasRestantes();
            $diasRestantes = is_null($resultado)
                ? 'No'
                : (string) $encuesta->diasRestantes();
            $numeroRespuestas = $encuesta->numeroRespuestas();

            $informe = [
                'titulo_encuesta' => $encuesta->titulo_encuesta,
                'dias_restantes' => $diasRestantes,
                'numero_respuestas' => $numeroRespuestas,
                // 'preguntas' => []
            ];

            if ($numeroRespuestas < 1) {
                return $informe;
            }

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
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function seleccionResultados(Pregunta $pregunta): array
    {
        try {
            $arrayMarcadas = [];
            $arrayCombinaciones = [];

            $respuestas = $pregunta->respuestas()->get('seleccion');

            foreach ($respuestas as $item) {
                if (is_null($item['seleccion'])) {
                    $arrayMarcadas[] = 'sin responder';
                } else {
                    foreach ($item['seleccion'] as $indice) {
                        $arrayMarcadas[] = (string)$pregunta->seleccion[$indice];
                    }
                    $opciones = $item['seleccion'];
                    sort($opciones);
                    $mappedSeleccion = array_map(fn($indice) => $pregunta->seleccion[$indice], $opciones);
                    $arrayCombinaciones[] = implode(' ', $mappedSeleccion);
                }
            }

            $frecuencia = Freq::frequencies($arrayMarcadas) ?? null;
            $porcentaje = Freq::relativeFrequencies($arrayMarcadas, 2) ?? null;
            $frecCombinaciones = Freq::frequencies($arrayCombinaciones) ?? null;
            $combinacionMenosElegida = $frecCombinaciones ? array_keys($frecCombinaciones, min($frecCombinaciones))[0] : null;
            $combinacionMasElegida = $frecCombinaciones ? array_keys($frecCombinaciones, max($frecCombinaciones))[0] : null;

            $resultadosFormateados = [];
            foreach ($frecuencia as $key => $value) {
                $resultadosFormateados[] = [
                    'titulo_opcion' => $key,
                    'resultado_opcion' => $value,
                    'porcentaje' => $porcentaje[$key]
                ];
            }

            $estadisticas = $combinacionMenosElegida
                ? [
                    'mas_popular' => $combinacionMasElegida,
                    'menos_popular' => $combinacionMenosElegida
                ]
                : [];

            return [
                'titulo_pregunta' => $pregunta->titulo_pregunta,
                'tipo_pregunta' => $pregunta->tipo_pregunta->value,
                'resultados' => $resultadosFormateados,
                'estadisticas' => $estadisticas
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function puntuacionResultados(Pregunta $pregunta): array
    {
        $arrayRespuestas = [];
        $respuestas = $pregunta->respuestas()->get(['puntuacion']);

        foreach ($respuestas as $item) {
            if (is_null($item['puntuacion'])) {
                $arrayRespuestas[] = 'sin responder';
            } else {
                $arrayRespuestas[] = $item['puntuacion'];
            }
        }
        $estadisticas = $this->estadisticas($arrayRespuestas);
        $frecuencia = Freq::frequencies($arrayRespuestas);
        $porcentaje = Freq::relativeFrequencies($arrayRespuestas, 2);

        $resultadosFormateados = [];
        foreach ($frecuencia as $key => $value) {
            $resultadosFormateados[] = [
                'titulo_opcion' => $key,
                'resultado_opcion' => $value,
                'porcentaje' => $porcentaje[$key]
            ];
        }

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'tipo_pregunta' => 'puntuación',
            // 'label_total_promedio' => 'Puntuación promedio',
            'resultados' => $resultadosFormateados,
            'estadisticas' => $estadisticas
        ];
    }

    private function numericoResultados(Pregunta $pregunta): array
    {
        $respuestasEnBlanco = 0;
        $totalRespuestas = 0;
        $numeroNoNulos = 0;

        $valoresNumericos = $pregunta->respuestas()->pluck('valor_numerico')->toArray();

        if (empty($valoresNumericos)) {
            return [
                'titulo_pregunta' => $pregunta->titulo_pregunta,
                'tipo_pregunta' => $pregunta->tipo_pregunta->value,
                'total_promedio' => 0,
                // 'label_total_promedio' => 'Valor promedio',
                'resultados' => [],
                'estadisticas' => []
            ];
        }

        $totalRespuestas = count($valoresNumericos);

        $valoresNoNulos = array_filter($valoresNumericos, function ($valor) {
            return !is_null($valor);
        });
        $numeroNoNulos = count($valoresNoNulos);
        $respuestasEnBlanco = $totalRespuestas - $numeroNoNulos;
        $max = max($valoresNoNulos);
        $min = min($valoresNoNulos);

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

        $estadisticas = $this->estadisticas($valoresNumericos);

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'tipo_pregunta' => 'valor numérico',
            // 'label_total_promedio' => 'Valor promedio obtenido de todas las respuestas',
            'resultados' => $resultadosFormateados,
            'estadisticas' => $estadisticas
        ];
    }


    private function textoResultados(Pregunta $pregunta): array
    {
        $respuestasEnBlanco = 0;
        $totalRespuestas = 0;
        $resultados = [
            'Respuestas cortas (-15 palabras)' => 0,
            'Respuestas medias (-50 palabras)' => 0,
            'Respuestas largas (+50 palabras)' => 0
        ];

        $respuestas = $pregunta->respuestas()->get(['entrada_texto']);
        if (!$respuestas || $respuestas->isEmpty()) {
            return [];
        }
        $arrayRespuestas = [];
        foreach ($respuestas as $respuesta) {
            $totalRespuestas++;
            if (is_null($respuesta->entrada_texto) || empty($respuesta->entrada_texto)) {
                $respuestasEnBlanco++;
            } else {
                $numPalabras = str_word_count($respuesta->entrada_texto);
                $arrayRespuestas[] = $numPalabras;
                if ($numPalabras < 15) {
                    $resultados['Respuestas cortas (-15 palabras)']++;
                } elseif ($numPalabras <= 50) {
                    $resultados['Respuestas medias (-50 palabras)']++;
                } else {
                    $resultados['Respuestas largas (+50 palabras)']++;
                }

                // Extraer palabras 
                $palabras = str_word_count($respuesta->entrada_texto, 1);
                $palabrasLargas = array_filter($palabras, fn($palabra) => strlen($palabra) > 4);

                // Contar palabras de más de 4 caracteres
                foreach ($palabrasLargas as $palabra) {
                    $frecuenciaPalabras[$palabra] = ($frecuenciaPalabras[$palabra] ?? 0) + 1;
                }

                // Obtener expresiones de 2 o 3 palabras
                $expresiones = [];
                for ($i = 0; $i < count($palabras) - 1; $i++) {
                    if (isset($palabras[$i + 2])) {
                        $expresiones[] = $palabras[$i] . ' ' . $palabras[$i + 1] . ' ' . $palabras[$i + 2];
                    }
                }

                // Contar las expresiones más usadas
                foreach ($expresiones as $expresion) {
                    $frecuenciaExpresiones[$expresion] = ($frecuenciaExpresiones[$expresion] ?? 0) + 1;
                }
            }
        }
        $estadisticas = $this->estadisticas($arrayRespuestas);

        // Obtener las palabras largas más usadas
        $frecuenciaPalabras = array_filter($frecuenciaPalabras, fn($count) => $count > 1);
        arsort($frecuenciaPalabras);
        $palabrasMasUsadas = array_slice($frecuenciaPalabras, 0, 10);

        // Obtener las expresiones más usadas
        $frecuenciaExpresiones = array_filter($frecuenciaExpresiones, fn($count) => $count > 1);
        arsort($frecuenciaExpresiones);
        $expresionesMasUsadas = array_slice($frecuenciaExpresiones, 0, 10);

        $resultadosFormateados = [];
        foreach ($resultados as $tipoRespuesta => $cantidad) {
            $porcentaje = $totalRespuestas > 0 ? ($cantidad / $totalRespuestas) * 100 : 0;
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

        $estadisticas['palabras_mas_usadas'] = $palabrasMasUsadas;
        $estadisticas['expresiones_mas_usadas'] = $expresionesMasUsadas;

        return [
            'titulo_pregunta' => $pregunta->titulo_pregunta,
            'tipo_pregunta' => 'texto libre',
            // 'label_total_promedio' => 'Número de palabras promedio por respuesta',
            'resultados' => $resultadosFormateados,
            'estadisticas' => $estadisticas,
        ];
    }

    private function estadisticas(array $respuestas)
    {
        $estadisticas = [];

        $data = array_filter($respuestas, fn($valor) => (!is_null($valor) && is_numeric($valor)));
        $data = array_values($data);

        // var_dump(json_encode($data));
        if (count($data) > 0) {
            $estadisticas['maximo'] = max($data);
            $estadisticas['minimo'] = min($data);
            $estadisticas['media'] = round(Stat::mean($data), 2);
            $estadisticas['mediana'] = round(Stat::median($data), 2);
            $estadisticas['moda'] = Stat::mode($data);
            $estadisticas['desviacion_estandar'] = Stat::stdev($data, 2);
            $estadisticas['cuartiles'] = Stat::quantiles($data, 4, 2); //Stat::quantiles( array $data, $n=4, $round=null )
            $estadisticas['frecuencia_por_intervalos'] = Freq::frequencyTable($data, 5);
        }
        return $estadisticas;
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
                return response()->json(['message' => 'Encuesta no encontrada'], 404);
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
