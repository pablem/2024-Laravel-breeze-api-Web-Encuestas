<?php

namespace Database\Seeders;

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

        // Verifica que haya usuarios antes de crear encuestas
        if (\App\Models\User::count() > 0) {
            Encuesta::factory(5)->create();
        } else {
            $this->command->info('No hay usuarios en la base de datos para asignar a las encuestas.');
        }

    }
}
