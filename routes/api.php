<?php

use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\EncuestadoController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MiembroEncuestaPrivadaController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RespuestaController;
use App\Http\Controllers\UserController;
use App\Models\Encuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

//Acceso: sólo Usuarios Administradores (y Superusuario)
Route::middleware(['auth:sanctum', 'role:Administrador'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{userId}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{userId}', [UserController::class, 'update']); 
    Route::delete('/users/{userId}', [UserController::class, 'destroy']);
});

//Acceso: Todos los Usuarios Logueados:
Route::middleware(['auth:sanctum'])->group(function () {
    //Perfil del mismo usuario
    Route::get('/profile', [UserController::class, 'showProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    //Encuestas
    Route::get('/encuestas', [EncuestaController::class, 'index']);
    Route::get('/encuestas/{encuestaId}/edit',[EncuestaController::class, 'edit']); //getEncuesta por id 
    //Publicar o Finalizar Encuestas 
    Route::put('encuestas/{encuestaId}/publicar',[EncuestaController::class, 'publicar']);
    Route::put('/encuestas/{encuestaId}/finalizar',[EncuestaController::class, 'finalizar']);
    //Encuestados
    Route::get('/encuestas/{encuestaId}/encuestados_sin_responder', [EncuestadoController::class, 'getEncuestadosSinResponder']);
    Route::get('/encuestados_con_correos', [EncuestadoController::class, 'getEncuestadosConCorreo']);
    Route::post('/encuestados',[EncuestadoController::class, 'store']);
    Route::put('/encuestados/{id}', [EncuestadoController::class, 'update']);
    Route::delete('/encuestados', [EncuestadoController::class, 'destroy']);
    //Miembros de una encuesta privada
    Route::post('/encuestas_privadas/{encuestaId}/miembro',[MiembroEncuestaPrivadaController::class, 'store']);
    //Feedback de encuestas piloto
    Route::get('/encuestas/{encuestaId}/feedback',[EncuestaController::class, 'getFeedbacks']);
    //Enviar emails
    Route::post('/encuestas/{encuestaId}/enviar_emails', [MailController::class, 'enviar']);
});

//Acceso: sólo editor, admin? y super (no publicador)
Route::middleware(['auth:sanctum', 'role:Administrador,Editor'])->group(function () {
    //Encuestas
    Route::post('/encuestas',[EncuestaController::class, 'store']);
    Route::put('/encuestas/{encuestaId}',[EncuestaController::class, 'update']);
    Route::delete('/encuestas/{encuestaId}',[EncuestaController::class, 'destroy']);
    Route::post('encuestas/{encuestaId}/nueva_version',[EncuestaController::class, 'nuevaVersion']);
    //Preguntas
    Route::post('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'store']);
    Route::delete('/preguntas/{preguntaId}',[PreguntaController::class, 'destroy']);
});

//Usuarios logueados y no logueados
//Encuestas
Route::get('/encuestas/publicada/{slug}', [EncuestaController::class, 'show']);
Route::post('/encuestas/publicada/{slug}/correo', [EncuestaController::class, 'showByMail']);
//Preguntas
Route::get('/encuestas/{encuestaId}/preguntas',[PreguntaController::class, 'getPreguntas']);
Route::post('/encuestas/responder',[RespuestaController::class, 'store']); //modificar: vector de ids
//Informes
Route::get('/informes/{encuestaId}',[InformeController::class, 'show']);

//Probando el proveedor (Mailtrap) con un texto plano
Route::get('/enviar_texto_simple', function() {
    Mail::to('usuario@gmail.com')
        ->send(new \App\Mail\TextoSimpleMailable);
})->name('simple'); 