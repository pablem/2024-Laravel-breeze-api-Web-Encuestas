<?php

namespace App\Http\Controllers;

use App\Enums\TipoPregunta;
use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use HiFolks\Statistics\Stat;
use HiFolks\Statistics\Freq;
use Illuminate\Support\Facades\DB;

// use phpDocumentor\Reflection\Types\This;

class InformeController extends Controller
{
    private function ratioRespuestas($id)
    {
        try {
            $conteos = DB::select('
            SELECT COUNT(Re.id) AS total_respuestas, 
                COUNT (Re.id) FILTER (
                    WHERE Re.entrada_texto IS NULL
                    AND Re.puntuacion IS NULL
                    AND Re.seleccion IS NULL
                    AND Re.valor_numerico IS NULL
                ) AS sin_responder
            FROM respuestas Re
            INNER JOIN preguntas Pr ON Pr.id = Re.pregunta_id
            WHERE Pr.encuesta_id = ?
            ', [$id]);

            $totalRespuestas = $conteos[0]->total_respuestas ?? 0;
            $totalSinResponder = $conteos[0]->sin_responder ?? 0;

            $porcentajeRespondidas = $totalRespuestas > 0
                ? (1 - ($totalSinResponder / $totalRespuestas)) * 100
                : 0;

            $ratioRespuestas = [
                'total' => $totalRespuestas,
                'completadas' => $totalRespuestas - $totalSinResponder,
                'sin_responder' => $totalSinResponder,
                'porcentaje' => round($porcentajeRespondidas, 2)
            ];

            return $ratioRespuestas;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    /**
     * Display the specified resource.
     * 
     * @param  Request  $request
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }
            if (!Auth::check()) {
                if ($request->has('encuestadoId') && $request->has('hash')) {
                    $encuestadoId = $request->input('encuestadoId');
                    $hash = $request->input('hash');
                    $correo = Encuestado::where('id', $encuestadoId)->pluck('correo')->first();
                    if (!$correo || !hash_equals(sha1($correo), (string) $hash)) {
                        return response()->json(['message' => 'Falló la verificación del correo del encuestado.'], 403);
                    }
                } else {
                    return response()->json(['message' => 'Los campos de validación están vacíos.'], 403);
                }
            }
            $informe = $this->generarInforme($encuesta);
            $informe['ratio_respuestas'] = $this->ratioRespuestas($encuestaId);
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
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }

            $informe = $this->generarInforme($encuesta);

            $callback = function () use ($informe) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Titulo Encuesta', $informe['titulo_encuesta']]);
                fputcsv($file, ['Fecha', $informe['fecha_informe']]);
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
     * Recupera todas las respuestas y descarga en formato .csv
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function tablaRespuestasCsv($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada.'], 404);
            }

            $respuestas = DB::select('
                SELECT Pr.id AS id_pregunta, 
                    Pr.tipo_pregunta, 
                    Pr.id_orden, 
                    Re.id AS id_respuesta, 
                    Re.created_at AS fecha,
                    Pr.seleccion AS opciones,
                    Re.seleccion, 
                (
                CASE 
                    WHEN Re.puntuacion IS NOT NULL THEN Re.puntuacion::text
                    WHEN Re.valor_numerico IS NOT NULL THEN Re.valor_numerico::text
                    WHEN Re.entrada_texto IS NOT NULL THEN Re.entrada_texto
                    ELSE NULL
                END
                ) AS clave_valor
                FROM preguntas Pr
                LEFT JOIN respuestas Re ON Pr.id = Re.pregunta_id
                WHERE Pr.encuesta_id = ?
                ORDER BY Pr.id, Re.id
            ', [$encuestaId]);

            $callback = function () use ($encuesta,  $respuestas) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Titulo Encuesta', $encuesta->titulo_encuesta]);
                fputcsv($file, []);

                fputcsv($file, ['id pregunta', 'tipo de pregunta', 'id respuesta', 'fecha', 'resultado']);
                foreach ($respuestas as $respuesta) {
                    $resultado = '';
                    if ($respuesta->clave_valor) {
                        $resultado = $respuesta->clave_valor;
                    } elseif (!is_null($respuesta->seleccion) && !empty($respuesta->seleccion)) {
                        $seleccion = json_decode($respuesta->seleccion, true);
                        $opciones = json_decode($respuesta->opciones, true);
                        sort($seleccion);
                        $mappedSeleccion = array_map(fn($indice) => $opciones[$indice], $seleccion);
                        $resultado = implode(' ', $mappedSeleccion);
                    }
                    fputcsv(
                        $file,
                        [
                            $respuesta->id_pregunta,
                            $respuesta->tipo_pregunta,
                            $respuesta->id_respuesta,
                            $respuesta->fecha,
                            $resultado,
                        ]
                    );
                }
                fclose($file);
            };

            $filename = 'tabla_respuestas_' . $encuestaId . '.csv';
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
            $encuesta = Encuesta::find($encuestaId, ['id', 'titulo_encuesta', 'fecha_finalizacion']);
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
                'fecha_informe' => now()->format('d-m-Y H:i:s'),
                'dias_restantes' => $diasRestantes,
                'numero_respuestas' => $numeroRespuestas,
                'preguntas' => []
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
                    $arrayCombinaciones[] = implode(' + ', $mappedSeleccion);
                }
            }

            $frecuencia = Freq::frequencies($arrayMarcadas) ?? null;
            $porcentaje = Freq::relativeFrequencies($arrayMarcadas, 2) ?? null;
            $frecCombinaciones = Freq::frequencies($arrayCombinaciones) ?? null;
            $combinacionMenosElegida = $frecCombinaciones ? array_keys($frecCombinaciones, min($frecCombinaciones))[0] : null;
            $combinacionMasElegida = $frecCombinaciones ? array_keys($frecCombinaciones, max($frecCombinaciones))[0] : null;

            arsort($frecCombinaciones);
            $top5 = array_slice($frecCombinaciones, 0, 5, true);
            $otros = array_slice($frecCombinaciones, 5);
            $otrosSum = array_sum($otros);
            if ($otrosSum > 0) {
                $top5['(otros)'] = $otrosSum;
            }

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
                    'menos_popular' => $combinacionMenosElegida,
                    'frecuencia_combinaciones' => $top5
                ]
                : [];

            return [
                'id_pregunta' => $pregunta->id,
                'titulo_pregunta' => $pregunta->id_orden . '. ' . $pregunta->titulo_pregunta,
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
            'id_pregunta' => $pregunta->id,
            'titulo_pregunta' => $pregunta->id_orden . '. ' . $pregunta->titulo_pregunta,
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
                'id_pregunta' => $pregunta->id,
                'titulo_pregunta' => $pregunta->id_orden . '. ' . $pregunta->titulo_pregunta,
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
        $max = $valoresNoNulos && $valoresNoNulos > 1 ? max($valoresNoNulos) : 0;
        $min = $valoresNoNulos && $valoresNoNulos > 1 ? min($valoresNoNulos) : 0;

        // Dividir el rango en 5 intervalos
        $n = count($valoresNoNulos) > 4 ? 5 : count($valoresNoNulos);
        if (!$n) $n = 1;
        $intervalo = ($max - $min) / $n;
        $contadores = array_fill(0, $n, 0);

        foreach ($valoresNoNulos as $valor) {
            if ($valor == $max) {
                $contadores[$n - 1]++;
            } else {
                $index = floor(($valor - $min) / $intervalo);
                $contadores[$index]++;
            }
        }

        $resultadosFormateados = [];

        for ($i = 0; $i < $n; $i++) {
            $tramoInicio = round($min + $i * $intervalo, 2);
            $tramoFin = round($min + ($i + 1) * $intervalo, 2);
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
            'id_pregunta' => $pregunta->id,
            'titulo_pregunta' => $pregunta->id_orden . '. ' . $pregunta->titulo_pregunta,
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
        $resultados = [];

        $respuestas = $pregunta->respuestas()->get(['entrada_texto']);
        if (!$respuestas || $respuestas->isEmpty()) {
            return [];
        }
        $arrayRespuestas = [];
        $frecuenciaPalabras = [];
        $frecuenciaExpresiones = [];
        foreach ($respuestas as $respuesta) {
            $totalRespuestas++;
            if (is_null($respuesta->entrada_texto) || empty($respuesta->entrada_texto)) {
                $respuestasEnBlanco++;
            } else {
                $numPalabras = str_word_count($respuesta->entrada_texto);
                $arrayRespuestas[] = $numPalabras;
                if ($numPalabras < 15) {
                    if (!isset($resultados['Respuestas cortas (-15 palabras)'])) {
                        $resultados['Respuestas cortas (-15 palabras)'] = 0;
                    }
                    $resultados['Respuestas cortas (-15 palabras)']++;
                } elseif ($numPalabras <= 50) {
                    if (!isset($resultados['Respuestas medias (-50 palabras)'])) {
                        $resultados['Respuestas medias (-50 palabras)'] = 0;
                    }
                    $resultados['Respuestas medias (-50 palabras)']++;
                } else {
                    if (!isset($resultados['Respuestas largas (+50 palabras)'])) {
                        $resultados['Respuestas largas (+50 palabras)'] = 0;
                    }
                    $resultados['Respuestas largas (+50 palabras)']++;
                }

                // Extraer palabras 
                $palabras = str_word_count($respuesta->entrada_texto, 1);
                $palabrasLargas = array_filter($palabras, fn($palabra) => strlen($palabra) > 3);

                // Contar palabras de más de 3 caracteres
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
        $estadisticas = $this->estadisticas($arrayRespuestas, true);

        // Obtener las palabras largas más usadas
        $frecuenciaPalabras = array_filter($frecuenciaPalabras, fn($count) => $count > 1);
        arsort($frecuenciaPalabras);
        $palabrasMasUsadas = !empty($frecuenciaPalabras) ? array_slice($frecuenciaPalabras, 0, 6) : null;
        // if($palabrasMasUsadas) ksort($palabrasMasUsadas);

        // Obtener las expresiones más usadas
        $frecuenciaExpresiones = array_filter($frecuenciaExpresiones, fn($count) => $count > 1);
        arsort($frecuenciaExpresiones);
        $expresionesMasUsadas = !empty($frecuenciaExpresiones) ? array_slice($frecuenciaExpresiones, 0, 6) : [0];
        // if($expresionesMasUsadas) ksort($expresionesMasUsadas);

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
            'id_pregunta' => $pregunta->id,
            'titulo_pregunta' => $pregunta->id_orden . '. ' . $pregunta->titulo_pregunta,
            'tipo_pregunta' => 'texto libre',
            // 'label_total_promedio' => 'Número de palabras promedio por respuesta',
            'resultados' => $resultadosFormateados,
            'estadisticas' => $estadisticas,
        ];
    }

    private function estadisticas(array $respuestas, bool $texto = null)
    {
        $estadisticas = [];

        $data = array_filter($respuestas, fn($valor) => (!is_null($valor) && is_numeric($valor)));
        $data = array_values($data);

        // var_dump(json_encode($data));
        if (count($data) > 1) {
            $estadisticas['maximo'] = max($data);
            $estadisticas['minimo'] = min($data);
            $estadisticas['mediana'] = round(Stat::median($data), 2);
            $estadisticas['media'] = round(Stat::mean($data), 2);
            if ($texto) return $estadisticas;
            $estadisticas['moda'] = Stat::mode($data);
            if (count($data) > 2) $estadisticas['cuartiles'] = Stat::quantiles($data, 4, 2); //Stat::quantiles( array $data, $n=4, $round=null )
            $estadisticas['desviacion_estandar'] = Stat::stdev($data, 2);
            // $estadisticas['frecuencia_por_intervalos'] = Freq::frequencyTable($data, 5);
            $frecuencias = Freq::frequencies($data);
            arsort($frecuencias);
            $max_value = reset($frecuencias);
            if ($max_value > 1) {
                $estadisticas['frecuencia_por_intervalos'] = array_slice($frecuencias, 0, 6, true);
            } else {
                // Si el valor máximo es 1, no mostrar ningún valor
                $estadisticas['frecuencia_por_intervalos'] = [0];
            }
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
