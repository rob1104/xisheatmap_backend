<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está logueado pero es Capturista, lo bloqueamos
        $rolesPermitidos = ['Administrador', 'Supervisor'];

        // Si el usuario está logueado pero su rol NO está en la lista permitida
        if (auth()->check() && !in_array(auth()->user()->role, $rolesPermitidos)) {
            abort(403, 'Acceso restringido. Tu rol actual es: ' . auth()->user()->role);
        }

        return $next($request);
    }
}
