<?php

namespace Database\Seeders;

use App\Models\Encuesta;
use App\Models\Encuestado;
use App\Models\MiembroEncuestaPrivada;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MiembroEncuestaPrivadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $encuestaId = Encuesta::where('es_privada', true)->first()->id;
        $encuestadosIds = Encuestado::where('correo', 'like', "%privado%")->pluck('id')->toArray();
        foreach ($encuestadosIds as $id) {
            MiembroEncuestaPrivada::create([
                'encuesta_id' => $encuestaId,
                'encuestado_id' => $id
            ]);
        }
    }
}
