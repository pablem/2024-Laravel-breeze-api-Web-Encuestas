<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Encuesta;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Encuesta>
 */
class EncuestaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
        'user_id' => User::inRandomOrder()->first()->id, // Asigna un usuario random
            'titulo_encuesta' => fake()->sentence(3),
            'id_encuesta_version' => 1,
            'descripcion' => fake()->paragraph(3),
            'url' => fake()->url,
            'estado' => fake()->randomElement(['piloto', 'publicada', 'borrador']),
            'fecha_publicacion' => fake()->dateTimeBetween('-3 months', 'now'),
            'fecha_finalizacion' => fake()->dateTimeBetween('now', '+3 months'),
            'es_privada' => fake()->numberBetween(0, 1),
            'es_anonima' => fake()->numberBetween(0, 1),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
