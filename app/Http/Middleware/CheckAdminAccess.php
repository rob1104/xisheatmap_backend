<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserRole;

class CheckAdminAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo Administrador y Coordinador de sector pueden acceder
        $rolesPermitidos = [UserRole::ADMINISTRADOR, UserRole::COORDINADOR_SECTOR];

        if (auth()->check() && !in_array(auth()->user()->role, $rolesPermitidos)) {
            abort(403, 'Acceso restringido. Tu rol actual es: ' . auth()->user()->role->value);
        }

        return $next($request);
    }
}
