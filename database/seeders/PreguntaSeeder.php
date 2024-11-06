<?php

namespace Database\Seeders;

use App\Enums\TipoPregunta;
use App\Models\Encuesta;
use App\Models\Pregunta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PreguntaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            TipoPregunta::Text->value,
            TipoPregunta::Multiple->value,
            TipoPregunta::Unique->value,
            TipoPregunta::List->value,
            TipoPregunta::Rating->value,
            TipoPregunta::Numeric->value,
        ];
        $opciones1 = ['Cine', 'Literatura', 'Teatro'];
        $opciones2 = ['Agua', 'Fuego'];
        $opciones3 = ['Jujuy', 'Tucumán', 'Catamarca', 'La Rioja'];


        // for ($i=1; $i < 7 ; $i++) {

        $encuestas = Encuesta::limit(10)->get();

        foreach ($encuestas as $encuesta) {
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 1,
                'tipo_pregunta' => $tipos[0],
                'titulo_pregunta' => 'Pregunta control texto'
            ]);
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 2,
                'tipo_pregunta' => $tipos[1],
                'titulo_pregunta' => 'Pregunta control multiple choice',
                'seleccion' => $opciones1
            ]);
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 3,
                'tipo_pregunta' => $tipos[2],
                'titulo_pregunta' => 'Pregunta control opción única',
                'seleccion' => $opciones2
            ]);
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 4,
                'tipo_pregunta' => $tipos[3],
                'titulo_pregunta' => 'Pregunta control lista desplegable',
                'seleccion' => $opciones3
            ]);
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 5,
                'tipo_pregunta' => $tipos[4],
                'titulo_pregunta' => 'Pregunta control rating o calificación',
                'rango_puntuacion' => [1, 10, 1]
            ]);
            Pregunta::create([
                'encuesta_id' => $encuesta->id,
                'id_orden' => 6,
                'tipo_pregunta' => $tipos[5],
                'titulo_pregunta' => 'Pregunta control valor numérico'
            ]);
        }

        $encuestaEjemplo = Encuesta::where('titulo_encuesta', 'Ejemplo Encuesta Sobre Capacitaciones de la Empresa')->first();
        if ($encuestaEjemplo) {
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 1,
                'tipo_pregunta' => $tipos[2],
                'titulo_pregunta' => '¿Asistió a la semana de capacitación el año pasado?',
                'seleccion' => ['Si', 'No'],
                'es_obligatoria' => true
            ]);
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 2,
                'tipo_pregunta' => $tipos[3],
                'titulo_pregunta' => 'Seleccione su provincia de residencia (NOA)',
                'seleccion' => ['Jujuy', 'Salta', 'Tucumán', 'Catamarca', 'Santiago del Estero', 'La Rioja'],
                'es_obligatoria' => true
            ]);
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 3,
                'tipo_pregunta' => $tipos[1],
                'titulo_pregunta' => '¿Hay algún tema que le interese incluir en nuestra semana de capacitación?',
                'seleccion' => ['Firma digital', 'Phishing', 'Malwares']
            ]);
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 4,
                'tipo_pregunta' => $tipos[0],
                'titulo_pregunta' => '¿Qué opinas sobre la duración de la semana de capacitación?'
            ]);
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 5,
                'tipo_pregunta' => $tipos[4],
                'titulo_pregunta' => 'Califique la relevancia de los temas tratados en la última capacitación',
                'rango_puntuacion' => [1, 5, 1]
            ]);
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 6,
                'tipo_pregunta' => $tipos[5],
                'titulo_pregunta' => '¿Cuántas capacitaciones ha tomado en el último año?'
            ]);
        
            Pregunta::create([
                'encuesta_id' => $encuestaEjemplo->id,
                'id_orden' => 7,
                'tipo_pregunta' => $tipos[2],
                'titulo_pregunta' => '¿Participaría en una capacitación similar el próximo año?',
                'seleccion' => ['Definitivamente sí', 'Probablemente sí', 'No estoy seguro', 'Probablemente no', 'Definitivamente no']
            ]);
        }
    }
}
