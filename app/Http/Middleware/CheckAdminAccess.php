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
        // Administrador, Coordinador de sector, Gestor seccional y Presidente de comité pueden acceder
        $rolesPermitidos = [
            UserRole::ADMINISTRADOR,
            UserRole::COORDINADOR_SECTOR,
            UserRole::GESTOR_SECCIONAL,
            UserRole::PRESIDENTE_COMITE
        ];

        if (auth()->check() && !in_array(auth()->user()->role, $rolesPermitidos)) {
            abort(403, 'Acceso restringido. Tu rol actual es: ' . auth()->user()->role->value);
        }

        return $next($request);
    }
}
