<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowedRoles = [
            UserRole::ADMINISTRADOR->value,
            UserRole::COORDINADOR_TERRITORIAL->value,
            UserRole::GESTOR_SECCIONAL->value,
            UserRole::PRESIDENTE_COMITE->value,
        ];

        if ($user && ! $user->hasAnyRole($allowedRoles)) {
            abort(403, 'Acceso restringido. Tu cuenta no tiene un rol habilitado para el panel.');
        }

        return $next($request);
    }
}
