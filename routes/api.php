<?php

use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\EncuestadoController;
use App\Http\Controllers\MiembroEncuestaPrivadaController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RespuestaController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/encuestas', [EncuestaController::class, 'index']);
Route::get('/encuestas/publicada/{slug}', [EncuestaController::class, 'show']);
Route::post('/encuestas/publicada/{slug}/correo', [EncuestaController::class, 'showByMail']);
Route::post('/encuestas',[EncuestaController::class, 'store']);
Route::get('/encuestas/{encuestaId}/edit',[EncuestaController::class, 'edit']);
Route::put('/encuestas/{encuestaId}',[EncuestaController::class, 'update']);
Route::delete('/encuestas/{encuestaId}',[EncuestaController::class, 'destroy']);
Route::post('encuestas/{encuestaId}/nueva_version',[EncuestaController::class, 'nuevaVersion']);
Route::put('encuestas/{encuestaId}/publicar',[EncuestaController::class, 'publicar']);
Route::put('/encuestas/{encuestaId}/finalizar',[EncuestaController::class, 'finalizar']);

Route::post('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'store']);
Route::get('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'getPreguntas']);
Route::delete('/preguntas/{preguntaId}',[PreguntaController::class, 'destroy']);

Route::post('/encuestas/responder',[RespuestaController::class, 'store']); //modificar: vector de ids

Route::post('/encuestados',[EncuestadoController::class, 'store']);
Route::get('/encuestados_con_correos', [EncuestadoController::class, 'getEncuestadosConCorreo']);
Route::put('/encuestados/{id}', [EncuestadoController::class, 'update']);
Route::delete('/encuestados', [EncuestadoController::class, 'destroy']);

Route::post('/encuestas_privadas/{encuestaId}/miembro',[MiembroEncuestaPrivadaController::class, 'store']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{userId}', [UserController::class, 'show']);
Route::get('/profile', [UserController::class, 'showProfile']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{userId?}', [UserController::class, 'update']); // Ruta opcional con userId
Route::delete('/users/{userId}', [UserController::class, 'destroy']);

