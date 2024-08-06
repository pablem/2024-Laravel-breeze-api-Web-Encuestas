<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyEmailWithoutLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Validar la firma de la URL
        if (! URL::hasValidSignature($request)) {
            abort(403, 'URL caída o inválida.');
        }

        // Recupera el usuario por ID
        $user = User::find($request->route('id'));

        if (! $user) {
            abort(404, 'Usuario no encontrado.');
        }

        // Verifica que los coreos encriptados coincidan
        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            abort(403, 'Verificación hash inválida.');
        }

        // Agrega el $user al request para posterior manejo
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
