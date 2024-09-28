<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

class DashboardController extends Controller
{
    /**
     * 
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        try {
            $dashboard['contadores_encuestas'] = $this->conteoEncuestas();
            $dashboard['ratio_respuestas'] = $this->ratioRespuestas();
            $dashboard['top_respondidas'] = $this->topRespondidas();
            $dashboard['novedades_encuestas'] = $this->obtenerEncuestasPorFecha(now()->format('Y-m-d'));
            // $dashboard['novedades_encuestas'] = $this->obtenerEncuestasPorFecha('2024-08-23');
            return response()->json($dashboard, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Cuenta encuestas totales, borradores, publicadas, finalizadas,
     * piloto, privadas y anónimas
     * 
     * @return \Illuminate\Http\Response
     */
    private function conteoEncuestas(): array
    {
        try {
            $conteos = DB::select('
                SELECT COUNT(id) AS total_encuestas, 
                    COUNT (id) FILTER (
                        WHERE estado = \'borrador\'
                    ) AS borradores,
                    COUNT (id) FILTER (
                        WHERE estado IN (\'piloto\', \'publicada\')
                        AND (fecha_finalizacion IS NULL OR fecha_finalizacion  > CURRENT_DATE)
                    ) AS publicadas,
                    COUNT (id) FILTER (
                        WHERE fecha_finalizacion IS NOT NULL
                        AND fecha_finalizacion <= CURRENT_DATE
                    ) AS finalizadas,
                    COUNT (id) FILTER (
                        WHERE estado = \'piloto\'
                        AND (fecha_finalizacion IS NULL OR fecha_finalizacion  > CURRENT_DATE)
                    ) AS modo_piloto,
                    COUNT (id) FILTER (
                        WHERE es_privada IS true 
                        AND (fecha_finalizacion IS NULL OR fecha_finalizacion  > CURRENT_DATE)
                    ) AS privadas,
                    COUNT (id) FILTER (
                        WHERE es_anonima IS true
                        AND (fecha_finalizacion IS NULL OR fecha_finalizacion  > CURRENT_DATE)
                    ) AS anonimas
                    FROM encuestas;
                ');

            return json_decode(json_encode($conteos[0]), true);
        } catch (\Throwable $th) {
            throw $th;
        }
        // return response()->json($conteos[0], 200);
        // } catch (\Throwable $th) {
        //     return response()->json(['error' => $th->getMessage()], 500);
        // }
    }

    private function ratioRespuestas(): array
    {
        try {
            $conteos = DB::select('
            SELECT 
                COUNT(Re.id) AS total_respuestas,
                COUNT(Re.id) FILTER (
                    WHERE Re.entrada_texto IS NULL
                    AND Re.puntuacion IS NULL
                    AND Re.seleccion IS NULL
                    AND Re.valor_numerico IS NULL
                ) AS sin_responder,
                SUM(CASE WHEN En.es_privada THEN 1 ELSE 0 END) AS total_privadas,
                SUM(CASE 
                    WHEN En.es_privada
                    AND Re.entrada_texto IS NULL
                    AND Re.puntuacion IS NULL
                    AND Re.seleccion IS NULL
                    AND Re.valor_numerico IS NULL 
                    THEN 1 ELSE 0 
                END) AS privadas_sin_responder 
            FROM respuestas Re
            INNER JOIN preguntas Pr ON Pr.id = Re.pregunta_id
            INNER JOIN encuestas En ON En.id = Pr.encuesta_id
            ');

            $conteoEncuestas = DB::select('
            SELECT 
                COUNT(DISTINCT Mi.id) AS esperadas,
                COUNT(DISTINCT Re.encuestado_id) AS respondidas
            FROM miembro_encuesta_privadas Mi
            INNER JOIN encuestas En ON En.id = Mi.encuesta_id
            INNER JOIN preguntas Pr ON Pr.encuesta_id = En.id
            LEFT JOIN respuestas Re ON Re.pregunta_id = Pr.id
            WHERE En.es_privada
            ');

            $totalRespuestas = $conteos[0]->total_respuestas;
            $totalSinResponder = $conteos[0]->sin_responder;
            $totalPrivadas = $conteos[0]->total_privadas;
            $privadasSinResponder = $conteos[0]->privadas_sin_responder;
            $encuestasEsperadas = $conteoEncuestas[0]->esperadas;
            $encuestasRespondidas = $conteoEncuestas[0]->respondidas;

            $porcentajeRespondidas = $totalRespuestas > 0
                ? (1 - ($totalSinResponder / $totalRespuestas)) * 100
                : 0;

            $porcentajePrivadasRespondidas = $totalPrivadas > 0
                ? (1 - ($privadasSinResponder / $totalPrivadas)) * 100
                : 0;

            $porcentajeEncuestasRespondidas = $encuestasEsperadas > 0
                ? ($encuestasRespondidas / $encuestasEsperadas) * 100
                : 0;

            return [
                // 'total_respuestas' => $totalRespuestas,
                // 'respondidas' => $totalRespuestas - $totalSinResponder,
                // 'sin_responder' => $totalSinResponder,
                'porcentaje_respondidas' => round($porcentajeRespondidas, 2),

                // 'total_respuestas_privadas' => $totalPrivadas,
                // 'respondidas_privadas' => $totalPrivadas - $privadasSinResponder,
                // 'privadas_sin_responder' => $privadasSinResponder,
                'porcentaje_respondidas_privadas' => round($porcentajePrivadasRespondidas, 2),

                // 'encuestas_privadas_esperadas' => $encuestasEsperadas,
                // 'encuestas_privadas_respondidas' => $encuestasRespondidas,
                // 'encuestas_privadas_sin_responder' => $encuestasEsperadas - $encuestasRespondidas,
                'porcentaje_encuestas_privadas_hechas' => round($porcentajeEncuestasRespondidas, 2)
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function topRespondidas(): array
    {
        try {
            $n = 5;
            $encuestas = DB::select('
            SELECT En.id AS id_encuesta, En.titulo_encuesta,
                COUNT(DISTINCT Re.encuestado_id) AS respondidas,
                COUNT(DISTINCT Re.encuestado_id) FILTER (
                    WHERE Re.entrada_texto IS NULL
                    AND Re.puntuacion IS NULL
                    AND Re.seleccion IS NULL
                    AND Re.valor_numerico IS NULL
                ) AS respondidas_incompletas
            FROM respuestas Re
            INNER JOIN preguntas Pr ON Re.pregunta_id = Pr.id
            INNER JOIN encuestas En ON Pr.encuesta_id = En.id
            GROUP BY En.id, En.titulo_encuesta
            ORDER BY respondidas DESC
            LIMIT ?
            ', [$n]);

            $response = collect($encuestas)->map(function ($encuesta, $index) {

                $porcentaje_incompletas = $encuesta->respondidas > 0
                    ? ($encuesta->respondidas_incompletas / $encuesta->respondidas) * 100
                    : 0;

                return [
                    'id_orden' => ($index + 1),
                    'id_encuesta' => $encuesta->id_encuesta,
                    'titulo_encuesta' => $encuesta->titulo_encuesta,
                    'respondidas' => $encuesta->respondidas,
                    'respondidas_incompletas' => $encuesta->respondidas_incompletas,
                    'porcentaje_incompletas' => round($porcentaje_incompletas, 2)
                ];
            });

            return $response->values()->all();
            // return response()->json($response->values()->all(), 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    // /**
    //  * Muestra el top 5 de encuestas más respondidas (versión simple) 
    //  * 
    //  * @return \Illuminate\Http\Response
    //  */
    // public function topRespondidasSimple()
    // {
    //     $n = 5;
    //     try {
    //         $encuestas = Encuesta::select('id', 'titulo_encuesta')
    //             ->get()
    //             ->sortByDesc(function ($encuesta) {
    //                 return $encuesta->numeroRespuestas();
    //             })
    //             ->take($n);

    //         $response = $encuestas->values()->map(function ($encuesta, $index) {
    //             return [
    //                 'titulo_indice' => 'Encuesta N° ' . ($index + 1),
    //                 'id_encuesta' => $encuesta->id,
    //                 'titulo_encuesta' => $encuesta->titulo_encuesta,
    //                 'respondidas' => $encuesta->numeroRespuestas(),
    //             ];
    //         });

    //         return response()->json($response->values()->all(), 200);
    //     } catch (\Throwable $th) {
    //         return response()->json(['error' => $th->getMessage()], 500);
    //     }
    // }

    /**
     * Muestra las encuestas con novedades en la fecha específica:
     * creadas, actualizadas, publicadas, finalizadas y/o comentadas
     * 
     * @param  \Illuminate\Http\Request $request 
     * @return \Illuminate\Http\Response
     */
    public function novedadesEncuestas(Request $request)
    {
        try {
            $limit = 10;
            $fecha = $request->has('fecha')
                ? $request->input('fecha')
                : now()->format('Y-m-d');

            $encuestas = $this->obtenerEncuestasPorFecha($fecha, $limit);

            // return [
            // ];

            return response()->json($encuestas, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    private function obtenerEncuestasPorFecha(string $fecha, int $limit = 10): array
    {
        return DB::select('
        WITH fecha_alias AS (
            SELECT ?::date AS fecha
        )
        SELECT 
            En.id, 
            En.titulo_encuesta,
            En.estado,
            CONCAT(
                CASE WHEN En.created_at::date = fa.fecha THEN \'Creada \' ELSE \'\' END,
                CASE WHEN En.updated_at::date = fa.fecha
                    AND En.created_at::date <> fa.fecha THEN \'Actualizada \' ELSE \'\' END,
                CASE WHEN En.fecha_publicacion::date = fa.fecha THEN \'Publicada \' ELSE \'\' END,
                CASE WHEN En.fecha_finalizacion::date = fa.fecha THEN \'Finalizada \' ELSE \'\' END,
                CASE WHEN Fe.created_at::date = fa.fecha THEN \'Comentada\' ELSE \'\' END
            ) AS actividad
        FROM 
            encuestas En
        LEFT JOIN 
            feedback_encuestas Fe ON Fe.encuesta_id = En.id 
        JOIN 
            fecha_alias fa ON true
        WHERE 
            En.created_at::date = fa.fecha
            OR En.updated_at::date = fa.fecha
            OR En.fecha_publicacion::date = fa.fecha
            OR En.fecha_finalizacion::date = fa.fecha
            OR Fe.created_at::date = fa.fecha
        LIMIT ?;
    ', [$fecha, $limit]);
    }
}
