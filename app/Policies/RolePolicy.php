<?php

namespace App\Policies;

use App\Enums\RolePermission;
use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Interceptor global: Administrador o roles.gestionar tienen bypass total.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(UserRole::ADMINISTRADOR->value) || $user->can(RolePermission::GESTIONAR->value)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(RolePermission::VER->value);
    }

    public function create(User $user): bool
    {
        return $user->can(RolePermission::CREAR->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(RolePermission::EDITAR->value);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(RolePermission::ELIMINAR->value);
    }
}