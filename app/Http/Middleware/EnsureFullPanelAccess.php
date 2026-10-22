<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Limita los módulos generales a roles operativos; los permisos puntuales abren su propio módulo. */
class EnsureFullPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasFullPanelAccess()) {
            $home = $user->panelHomeRoute();

            if ($home !== null && $request->routeIs('dashboard')) {
                return redirect()->route($home);
            }

            abort(403, 'Acceso restringido. Tu rol no tiene acceso a este módulo.');
        }

        return $next($request);
    }
}
