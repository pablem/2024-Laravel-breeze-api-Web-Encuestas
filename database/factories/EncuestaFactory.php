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
        $esPrivada = fake()->boolean();
        $esAnonima = fake()->boolean();
        if ($esPrivada && $esAnonima) {
            $esAnonima = false;
            $esPrivada = false;
        }

        return [
            'user_id' => User::inRandomOrder()->first()->id, // Asigna un usuario random
            // 'id_versionamiento' => 1,
            'titulo_encuesta' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(3),
            // 'url' => fake()->url,
            'estado' => fake()->randomElement(['piloto', 'publicada', 'borrador']),
            'fecha_publicacion' => fake()->dateTimeBetween('-3 months', 'now'),
            'fecha_finalizacion' => fake()->dateTimeBetween('now', '+3 months'),
            'es_privada' => $esPrivada,
            'es_anonima' => $esAnonima,
            // 'version' => 1,
            'limite_respuestas' => fake()->numberBetween(0, 100),
        ];
    }
}
