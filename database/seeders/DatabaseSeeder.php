<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        //Se hace una lista de llamadas a los seeders en orden, manteniendo el código de cada seeder desacoplado
            UserSeeder::class,
            EncuestaSeeder::class
        ]);
    }
}
