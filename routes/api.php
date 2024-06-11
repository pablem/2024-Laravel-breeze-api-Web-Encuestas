<?php

use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RespuestaController;
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

Route::post('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'store']);
Route::get('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'getPreguntas']);
Route::delete('/preguntas/{preguntaId}',[PreguntaController::class, 'destroy']);

Route::post('/encuestas/{encuestaId}/responder',[RespuestaController::class, 'store']);

