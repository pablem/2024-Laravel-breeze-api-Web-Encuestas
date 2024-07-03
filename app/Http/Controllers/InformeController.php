<?php

namespace App\Http\Controllers;

use App\Enums\TipoPregunta;
use App\Models\Encuesta;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\Request;

class InformeController extends Controller
{
    public function temporal($id) {
        try {
            $encuesta = Encuesta::find($id, ['titulo_encuesta']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }
            $preguntas = Pregunta::where('encuesta_id', $id)->orderBy('id_orden')->get(); //->select('id', 'titulo_pregunta', 'tipo_pregunta', 'seleccion', 'rango_puntuacion')
            
            $pregunta = $preguntas->first();

            $respuestas = $pregunta->respuestas()->get(['puntuacion']);

            return response()->json($respuestas, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }
    /**
     * Display the specified resource.
     * 
     * @param  int  $encuestaId
     * @return \Illuminate\Http\Response
     */
    public function show($encuestaId)
    {
        try {
            $encuesta = Encuesta::find($encuestaId, ['titulo_encuesta']);
            if (is_null($encuesta)) {
                return response()->json(['error' => 'Encuesta no encontrada'], 404);
            }

            $informe = [
                'titulo_encuesta' => $encuesta->titulo_encuesta,
                'preguntas' => []
            ];

            $preguntas = Pregunta::where('encuesta_id', $encuestaId)->orderBy('id_orden')->get(); //->select('id', 'titulo_pregunta', 'tipo_pregunta', 'seleccion', 'rango_puntuacion')

            foreach ($preguntas as $pregunta) {

                $resultados = [];

                if (
                    $pregunta->tipo_pregunta->value === TipoPregunta::Multiple->value ||
                    $pregunta->tipo_pregunta->value === TipoPregunta::Unique->value ||
                    $pregunta->tipo_pregunta->value === TipoPregunta::List->value
                ) {

                    $resultados = $this->seleccionResultados($pregunta);
                }
                if ($pregunta->tipo_pregunta->value === TipoPregunta::Rating->value) {

                    $resultados = $this->puntuacionResultados($pregunta);
                }
                if ($pregunta->tipo_pregunta->value === TipoPregunta::Text->value) {

                    $resultados = $this->textoResultados($pregunta);
                }

                $informe['preguntas'][] = $resultados;
            }

            return response()->json($informe, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
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
}
