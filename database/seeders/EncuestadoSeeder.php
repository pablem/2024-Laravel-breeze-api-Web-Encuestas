<?php

namespace Database\Seeders;

use App\Models\Encuestado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EncuestadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Encuestado::factory(10)->create();
        
        Encuestado::create([
            'correo' => 'privado1@privado'
        ]);
        Encuestado::create([
            'correo' => 'privado2@privado'
        ]);
        Encuestado::create([
            'correo' => 'privado3@privado'
        ]);
    }
}
