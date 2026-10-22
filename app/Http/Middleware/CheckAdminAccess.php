<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canAccessPanel()) {
            abort(403, 'Acceso restringido. Tu cuenta no tiene un rol habilitado para el panel.');
        }

        return $next($request);
    }
}
