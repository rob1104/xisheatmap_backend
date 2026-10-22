<?php

namespace Database\Seeders;

use App\Enums\ListaNominalPermission;
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
        if (! User::hasSpatiePermissionTables()) {
            throw new \RuntimeException('Las tablas de Spatie Permission no existen. Ejecuta las migraciones antes del seeder.');
        }

        // Limpia caché interna de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crea los permisos a partir de los casos de ListaNominalPermission
        foreach (ListaNominalPermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // Matriz de asignación de permisos usando los 6 roles canónicos
        $rolePermissions = [
            // Administrador: Todos los permisos del módulo
            UserRole::ADMINISTRADOR->value => ListaNominalPermission::values(),

            // Roles operativos con permiso únicamente de lectura/visualización
            UserRole::COORDINADOR_TERRITORIAL->value => [
                ListaNominalPermission::VER->value,
            ],
            UserRole::GESTOR_SECCIONAL->value => [
                ListaNominalPermission::VER->value,
            ],

            // Roles inferiores sin permisos sobre el módulo
            UserRole::PRESIDENTE_COMITE->value => [],
            UserRole::INTEGRANTE_COMITE->value => [],
            UserRole::AFINES->value => [],
        ];

        $modulePermissions = ListaNominalPermission::values();

        // Crear/Obtener roles canónicos y sincronizar únicamente los permisos de lista nominal
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

        // Sincronización de rol Spatie para usuarios existentes únicamente si aún existe la columna física
        if (Schema::hasColumn('users', 'role')) {
            User::whereNotNull('role')->cursor()->each(function (User $user) {
                $user->syncRoleWithSpatie();
            });
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
