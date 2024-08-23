<?php

namespace Database\Seeders;

use App\Models\Encuesta;
use App\Models\Feedback_encuesta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackEncuestaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $encuestas = Encuesta::select('id')->where('estado', 'piloto')->limit(2)->get();

        foreach ($encuestas as $e) {
            for ($i = 0; $i < 9; $i++) {
                Feedback_encuesta::create([
                    'encuesta_id' => $e->id,
                    'comentarios' => fake()->sentence(mt_rand(0, 50))
                ]);
            }
        }
    }
}
