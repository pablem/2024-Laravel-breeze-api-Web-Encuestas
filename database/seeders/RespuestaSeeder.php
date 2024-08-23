<?php

namespace Database\Seeders;

use App\Enums\TipoPregunta;
use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RespuestaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $encuestasIds = Encuesta::limit(3)->pluck('id')->toArray();
        $encuestadosIds = Encuestado::limit(10)->pluck('id')->toArray();
        $preguntas = Pregunta::select('id', 'tipo_pregunta')->whereIn('encuesta_id', $encuestasIds)->get();

        foreach ($encuestadosIds as $id) {
            foreach ($preguntas as $pregunta) {
                switch ($pregunta->tipo_pregunta) {
                    case TipoPregunta::Text:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'entrada_texto' => $id % 2 === 0
                                ? fake()->sentence(mt_rand(0, 75))
                                : fake()->optional()->sentence(mt_rand(0, 75))
                        ]);
                        break;
                    case TipoPregunta::Multiple:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'seleccion' => $id % 2 === 0
                                ? fake()->randomElements([0, 1, 2], mt_rand(1, 3))
                                : fake()->optional()->randomElements([0, 1, 2], mt_rand(1, 3))
                        ]);
                        break;
                    case TipoPregunta::Unique:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'seleccion' => $id % 2 === 0
                                ? fake()->randomElements([0, 1], 1)
                                : fake()->optional()->randomElements([0, 1], 1)
                        ]);
                        break;
                    case TipoPregunta::List:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'seleccion' => $id % 2 === 0
                                ? fake()->randomElements([0, 1, 2, 3], 1)
                                : fake()->optional()->randomElements([0, 1, 2, 3], 1)
                        ]);
                        break;
                    case TipoPregunta::Rating:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'puntuacion' => $id % 2 === 0
                                ? fake()->numberBetween(1, 10)
                                : fake()->optional()->numberBetween(1, 10)
                        ]);
                        break;
                    case TipoPregunta::Numeric:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                            'valor_numerico' => $id % 2 === 0
                                ? fake()->randomFloat(2, 0, 200)
                                : fake()->optional()->randomFloat(2, 0, 200)
                        ]);
                        break;

                    default:
                        Respuesta::create([
                            'encuestado_id' => $id,
                            'pregunta_id' => $pregunta->id,
                        ]);
                        break;
                }
            }
        }
    }
}
