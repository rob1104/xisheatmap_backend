<?php

namespace Database\Seeders;

use App\Enums\ListaNominalPermission;
use App\Enums\RolePermission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ListaNominalPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpia caché interna de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crea los permisos de Lista Nominal
        foreach (ListaNominalPermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // Crea los permisos del módulo de Roles (roles.ver, roles.crear, roles.editar, roles.eliminar, roles.gestionar)
        foreach (RolePermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        $allSystemPermissions = array_merge(
            ListaNominalPermission::values(),
            RolePermission::values()
        );

        // Matriz de asignación de permisos usando los 6 roles canónicos
        $rolePermissions = [
            // Administrador: Todos los permisos de Lista Nominal y Gestión de Roles
            UserRole::ADMINISTRADOR->value => $allSystemPermissions,

            // Roles operativos con permiso únicamente de lectura/visualización en Lista Nominal
            UserRole::COORDINADOR_TERRITORIAL->value => [
                ListaNominalPermission::VER->value,
            ],
            UserRole::GESTOR_SECCIONAL->value => [
                ListaNominalPermission::VER->value,
            ],

            // Roles inferiores sin permisos administrativos
            UserRole::PRESIDENTE_COMITE->value => [],
            UserRole::INTEGRANTE_COMITE->value => [],
            UserRole::AFINES->value => [],
        ];

        $modulePermissions = ListaNominalPermission::values();

        // Crear/Obtener roles canónicos y sincronizar únicamente los permisos de lista nominal (y roles para Admin)
        foreach ($rolePermissions as $roleName => $permission) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissionToGive = $permission;
            $permissionToRevoke = array_diff($modulePermissions, $permissionToGive);

            if ($permissionToGive) {
                $role->givePermissionTo($permissionToGive);
            }

            if ($permissionToRevoke) {
                $role->revokePermissionTo($permissionToRevoke);
            }
        }

        // Sincronización de rol Spatie para usuarios existentes
        if (Schema::hasColumn('users', 'role')) {
            User::whereNotNull('role')->cursor()->each(function (User $user) {
                $user->syncRoleWithSpatie();
            });
        } else {
            // Si la columna users.role ya fue eliminada, asegurar que ningún usuario quede huérfano sin rol en Spatie
            User::doesntHave('roles')->cursor()->each(function (User $user) {
                $user->syncRoles([UserRole::GESTOR_SECCIONAL->value]);
            });
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}