<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['status' => 'verification-link-sent']);
    }

    /**
     * El administrador manda a un usuario especidico un nuevo email de verificación
     */
    public function storeAdmin($userId)
    {
        try {
            $usuario = User::find($userId);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            if ($usuario->hasVerifiedEmail()) {
                return response()->json(['message' => 'El usuario ya está verificado.'], 400);
            }
            $usuario->sendEmailVerificationNotification();
    
            return response()->json(['message' => 'El email ha sido enviado con éxito.'], 201);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
