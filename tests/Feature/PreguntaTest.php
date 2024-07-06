<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Pregunta;
use App\Models\Encuesta;
use App\Models\User;

class PreguntaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_store_and_update_preguntas()
    {
        $this->withoutExceptionHandling();

        //Crea un usuario autenticado
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crea una encuesta de ejemplo
        $encuesta = Encuesta::factory()->create();

        // Define el payload para la prueba
        $payload = [
            [
                //'id' => 1,
                'id_orden' => 1,
                'titulo_pregunta' => 'Pregunta 1',
                'tipo_pregunta' => 'text',
                'seleccion' => null,
                'rango_puntuacion' => null,
                'es_obligatoria' => true
            ],
            [
                //'id' => 2,
                'id_orden' => 2,
                'titulo_pregunta' => 'Pregunta 2',
                'tipo_pregunta' => 'multiple choice',
                'seleccion' => ['opcion1', 'opcion2'],
                'rango_puntuacion' => null,
                'es_obligatoria' => true
            ]
        ];

        // Realiza la llamada al método store del controlador
        $response = $this->postJson("/api/encuestas/{$encuesta->id}/preguntas", $payload);

        // Verifica la respuesta
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Se guardaron las preguntas']);

        // Verifica que las preguntas se hayan guardado en la base de datos
        $this->assertDatabaseHas('preguntas', [
           // 'id' => 1,
            'encuesta_id' => $encuesta->id,
            'id_orden' => 1,
            'titulo_pregunta' => 'Pregunta 1',
            'tipo_pregunta' => 'text',
            'seleccion' => null,
            'rango_puntuacion' => null,
            'es_obligatoria' => false
        ]);

        $this->assertDatabaseHas('preguntas', [
            //'id' => 2,
            'encuesta_id' => $encuesta->id,
            'id_orden' => 2,
            'titulo_pregunta' => 'Pregunta 2',
            'tipo_pregunta' => 'multiple choice',
            'seleccion' => json_encode(['opcion1', 'opcion2']),
            'rango_puntuacion' => null,
            'es_obligatoria' => true
        ]);
    }
}