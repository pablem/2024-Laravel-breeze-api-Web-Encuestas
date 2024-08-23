<?php

namespace Database\Seeders;

use App\Enums\EstadoEncuesta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Encuesta;

class EncuestaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //if user id 1 super exist 
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control publicada I',
            'descripcion' => fake()->paragraph(1),
            'url' => 'http://localhost:5173/encuesta/publicada/encuesta-control-publicada-i-1',
            'estado' => EstadoEncuesta::Publicada->value,
            'fecha_publicacion' => now()
        ]);
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control publicada piloto II',
            'descripcion' => fake()->paragraph(1),
            'url' => 'http://localhost:5173/encuesta/publicada/encuesta-control-publicada-piloto-ii-1',
            'estado' => EstadoEncuesta::Piloto->value,
            'fecha_publicacion' => now()
        ]);
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control publicada piloto III',
            'descripcion' => fake()->paragraph(1),
            'url' => 'http://localhost:5173/encuesta/publicada/encuesta-control-publicada-piloto-iii-1',
            'estado' => EstadoEncuesta::Piloto->value,
            'fecha_publicacion' => now()
        ]);
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control privada',
            'descripcion' => fake()->paragraph(2),
            'url' => 'http://localhost:5173/encuesta/publicada/encuesta-control-privada-1',
            'estado' => EstadoEncuesta::Publicada->value,
            'fecha_publicacion' => now(),
            'es_privada' => true
        ]);
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control anonima',
            'descripcion' => fake()->paragraph(2),
            'url' => 'http://localhost:5173/encuesta/publicada/encuesta-control-anonima-1',
            'estado' => EstadoEncuesta::Publicada->value,
            'fecha_publicacion' => now(),
            'es_anonima' => true
        ]);
        Encuesta::create([
            'user_id' => 1,
            'titulo_encuesta' => 'Encuesta control borrador',
            'descripcion' => fake()->paragraph(3),
        ]);
        // Verifica que haya usuarios antes de crear encuestas
        if (\App\Models\User::count() > 0) {
            Encuesta::factory(12)->create();
        } else {
            $this->command->info('No hay usuarios en la base de datos para asignar a las encuestas.');
        }

    }
}
