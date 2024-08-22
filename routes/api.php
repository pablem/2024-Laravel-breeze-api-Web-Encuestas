<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\EncuestadoController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MiembroEncuestaPrivadaController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RespuestaController;
use App\Http\Controllers\UserController;
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
    Route::delete('/users/{userId}', [UserController::class, 'destroy']);
});

//Acceso: Todos los Usuarios Logueados:
Route::middleware(['auth:sanctum'])->group(function () {
    //Perfil del mismo usuario
    Route::get('/profile', [UserController::class, 'showProfile']);
    Route::put('/users/{userId}', [UserController::class, 'update']);
    //Encuestas
    Route::get('/encuestas', [EncuestaController::class, 'index']);
    Route::get('/encuestas/{encuestaId}/edit', [EncuestaController::class, 'edit']); //getEncuesta por id 
    //Publicar o Finalizar Encuestas 
    Route::put('encuestas/{encuestaId}/publicar', [EncuestaController::class, 'publicar']);
    Route::put('/encuestas/{encuestaId}/finalizar', [EncuestaController::class, 'finalizar']);
    //Encuestados
    Route::get('/encuestados_con_correo', [EncuestadoController::class, 'getEncuestadosConCorreo']);
    Route::get('/encuestados_sin_responder/{encuestaId}', [EncuestadoController::class, 'getEncuestadosSinResponder']);
    Route::post('/encuestados', [EncuestadoController::class, 'store']);
    Route::delete('/encuestados', [EncuestadoController::class, 'destroy']);
    //Miembros de una encuesta privada
    Route::get('/miembros_privados/{encuestaId}', [MiembroEncuestaPrivadaController::class, 'getMiembrosIds']);
    Route::delete('/miembros_privados/{encuestaId}', [MiembroEncuestaPrivadaController::class, 'destroy']);
    Route::post('/miembros_privados/{encuestaId}', [MiembroEncuestaPrivadaController::class, 'store']);
    //Feedback de encuestas piloto
    Route::get('/encuestas/{encuestaId}/feedback', [EncuestaController::class, 'getFeedbacks']);
    Route::get('/preguntas/{preguntaId}/lista_texto', [EncuestaController::class, 'getTextResponse']);
    //Enviar emails
    Route::post('/encuestas/{encuestaId}/enviar_correos', [MailController::class, 'enviarCorreos']);
    //Informes
    Route::get('/encuestas/{encuestaId}/informe_csv', [InformeController::class, 'downloadCsv']);
    Route::get('/encuestas/{encuestaId}/informe_pdf', [InformeController::class, 'downloadPdf']);
    //Imprimir Encuesta 
    Route::get('/encuestas/{encuestaId}/pdf', [InformeController::class, 'downloadSurveyPdf']);
    //dashboard
    Route::get('/dashboard/contadores_encuestas', [DashboardController::class, 'conteoEncuestas']); //dashboard/{idFuncion}
    Route::get('/dashboard/ratio_respuestas', [DashboardController::class, 'ratioRespuestas']);
    Route::get('/dashboard/top_respondidas', [DashboardController::class, 'topRespondidas']);
    Route::get('/dashboard/novedades_encuestas', [DashboardController::class, 'novedadesEncuestas']);
});

//Acceso: sólo editor, admin? y super (no publicador)
Route::middleware(['auth:sanctum', 'role:Administrador,Editor'])->group(function () {
    //Encuestas
    Route::post('/encuestas', [EncuestaController::class, 'store']);
    Route::put('/encuestas/{encuestaId}', [EncuestaController::class, 'update']);
    Route::delete('/encuestas/{encuestaId}', [EncuestaController::class, 'destroy']);
    Route::post('encuestas/{encuestaId}/nueva_version', [EncuestaController::class, 'nuevaVersion']);
    //Preguntas
    Route::post('/encuestas/{encuestaId}/preguntas', [PreguntaController::class, 'store']);
    Route::delete('/preguntas/{preguntaId}', [PreguntaController::class, 'destroy']);
});

//Usuarios logueados y no logueados
//Encuestas
Route::post('/encuestas/publicada/{slug}', [EncuestaController::class, 'showByCollectiveLink']);
Route::get('/encuestas/publicada/{slug}/{encuestadoId}/{hash}', [EncuestaController::class, 'showByIndividualLink']);
//Preguntas
Route::get('/encuestas/{encuestaId}/preguntas', [PreguntaController::class, 'getPreguntas']);
Route::post('/encuestas/{encuestaId}/responder', [RespuestaController::class, 'store']); //modificar: vector de ids
//Informes
Route::get('/encuestas/{encuestaId}/informe', [InformeController::class, 'show']);

//Probando el proveedor (Mailtrap) con un texto plano
Route::get('/enviar_texto_simple', function () {
    Mail::to('usuario@gmail.com')
        ->send(new \App\Mail\TextoSimpleMailable);
})->name('simple');
