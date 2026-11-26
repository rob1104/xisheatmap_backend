<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe los endpoints móviles de campo a los roles habilitados de UserRole::fieldRoles().
 */
class EnsureMobileFieldRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(UserRole::fieldRoles())) {
            return response()->json([
                'message' => 'Acceso denegado. Tu rol no está habilitado para operar en la aplicación móvil.',
            ], 403);
        }

        return $next($request);
    }
}
