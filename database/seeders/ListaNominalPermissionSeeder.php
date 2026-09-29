<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\UserRole;
use App\Enums\ListaNominalPermission;
use App\Models\User;
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
        // Limpa caché interna de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crea los permiso a partir de los caso de ListaNominalPermission
        foreach (ListaNominalPermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value, 
                'guard_name' => 'web'
            ]);
        }

        // Matiz de asignación de permisos
        $rolePermissions = [
            // Administrador: Todos los permisos del módulo
            UserRole::ADMINISTRADOR->value => ListaNominalPermission::values(),
            
            // Roles operativos con permiso únicamente de lectura/visualización
            UserRole::COORDINADOR_SECTOR->value => [
                ListaNominalPermission::VER->value,
            ],
            UserRole::GESTOR_SECCIONAL->value => [
                ListaNominalPermission::VER->value,
            ],
            
            // Roles inferiores sin permisos sobre el módulo
            UserRole::PRESIDENTE_COMITE->value => [],
            UserRole::INTEGRANTE_COMITE->value => [],
            UserRole::DESDOBLE->value => [],
        ];

        $modulePermissions = ListaNominalPermission::values();

        // Crear/Obtener roles y sincronizar únicamente los permisos de lista nominal
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
        User::whereNotNull('role')->cursor()->each(function (User $user) {
            $user->syncRoleWithSpatie();
        });
    }
}
