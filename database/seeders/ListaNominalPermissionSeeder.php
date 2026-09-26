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

        // Crear/Obtener roles y sincronizar los permisos
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissions);
        }
        
        // Asignacion de rol Spatie a los usuario existentes según el enum de su columna

        User::whereNotNull('role')->cursor()->each(function (User $user) {
            $roleName  = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

            if ($roleName && Role::Where('name', $roleName)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$roleName]);
            }
        });
    }
}
