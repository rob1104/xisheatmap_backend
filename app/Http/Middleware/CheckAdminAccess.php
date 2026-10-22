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
        // Administrador, Coordinador territorial, Gestor seccional y Presidente de comité pueden acceder
        $rolesPermitidos = [
            UserRole::ADMINISTRADOR,
            UserRole::COORDINADOR_SECTOR,
            UserRole::GESTOR_SECCIONAL,
            UserRole::PRESIDENTE_COMITE
        ];

        if (auth()->check()) {
            $user = auth()->user();
            $userRole = $user->role;

            // Permitir acceso si pertenece a los roles canónicos permitidos o si un rol personalizado cuenta con permisos de panel
            $hasPanelPermission = is_string($userRole) && $user->getAllPermissions()->isNotEmpty();

            if (! in_array($userRole, $rolesPermitidos, true) && ! $hasPanelPermission) {
                $rolName = $userRole instanceof \BackedEnum
                    ? $userRole->value
                    : (is_string($userRole) && $userRole !== '' ? $userRole : 'Sin rol');
                abort(403, 'Acceso restringido. Tu rol actual es: ' . $rolName);
            }
        }

        return $next($request);
    }
}
