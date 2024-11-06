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

        //RESPUESTAS SÓLO TEXTO

        $preguntaId = Pregunta::where('encuesta_id', 5)
            ->where('tipo_pregunta', 'text')
            ->value('id');
        $respuestasTexto = [
            "Fue una experiencia muy enriquecedora y también muy útil para todos los participantes.",
            "Considero que los temas fueron interesantes y variados, aunque faltó algo de profundidad en algunos temas.",
            "Me gustaria que en las futuras capacitaciones se incluyan muchos más ejercicios prácticos y variados.",
            "Las capacitaciones son esenciales para el desarrollo profesional y personal de los empleados.",
            "Hubo ciertos temas que ya conocía bien, pero igual aprendí muchas cosas nuevas e interesantes.",
            "Estoy muy satisfecho con la capacitación en general. ¡Espero que se repita muy pronto y con más temas!",
            "Creo que la duración fue adecuada para los temas seleccionados, pero algunos temas podrían extenderse.",
            "Me gustaría ver un enfoque mucho más técnico y específico en las próximas sesiones.",
            "Excelente oportunidad para mejorar habilidades profesionales y personales de los asistentes.",
            "Fue una capacitación muy productiva, aunque se podría mejorar en algunos aspectos del contenido."
        ];
        foreach ($encuestadosIds as $i => $id) {
            Respuesta::create([
                'encuestado_id' => $id,
                'pregunta_id' => $preguntaId,
                'entrada_texto' => $respuestasTexto[$i]
            ]);
        }

        //RESPUESTAS PARA PROBAR EL CONTADOR DE EXPRESIONES REPETIDAS 
        $preguntaId = Pregunta::where('encuesta_id', 8)
            ->where('tipo_pregunta', 'text')
            ->value('id');
        foreach ($encuestadosIds as $id) {
            Respuesta::create([
                'encuestado_id' => $id,
                'pregunta_id' => $preguntaId,
                'entrada_texto' => $id % 2 === 0
                    ? fake()->sentence(mt_rand(0, 75)) . ' frase repetida ejemplo'
                    : fake()->optional()->sentence(mt_rand(0, 75)) . ' probando el contador'
            ]);
        }
    }
}
