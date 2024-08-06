<?php
 
namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

     public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $usuario = $request->user();
        if($usuario->hasRole(UserRole::Super->value)) {
            return $next($request);
        }
        foreach ($roles as $role) {
            if ($usuario->hasRole($role)) {
                return $next($request);
            }
        }
        return response()->json(['error' => 'No tiene permisos para acceder a esta ruta.'], 403);
    }
 
}