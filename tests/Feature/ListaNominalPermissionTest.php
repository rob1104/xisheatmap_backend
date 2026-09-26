<?php

namespace Tests\Feature;

use App\Enums\ListaNominalPermission;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ListaNominalPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ListaNominalPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar la caché de permisos antes de cada prueba
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * 1. Verifica que el seeder dé de alta todos los permisos del Enum.
     */
    public function test_seeder_registers_all_permissions_from_enum(): void
    {
        $this->seed(ListaNominalPermissionSeeder::class);

        foreach (ListaNominalPermission::cases() as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        $this->assertEquals(
            count(ListaNominalPermission::cases()),
            Permission::count()
        );
    }

    /**
     * 2. Verifica que el Administrador tenga todos los permisos del módulo.
     */
    public function test_administrador_has_all_lista_nominal_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMINISTRADOR,
        ]);

        $this->seed(ListaNominalPermissionSeeder::class);
        $admin->refresh();

        $this->assertTrue($admin->hasRole(UserRole::ADMINISTRADOR->value));

        foreach (ListaNominalPermission::cases() as $permission) {
            $this->assertTrue(
                $admin->can($permission->value),
                "El Administrador debería tener el permiso: {$permission->value}"
            );
        }
    }

    /**
     * 3. Verifica que Coordinador de Sector y Gestor Seccional solo tengan permiso de lectura (ver).
     */
    public function test_coordinador_and_gestor_have_only_view_permission(): void
    {
        $coordinador = User::factory()->create([
            'role' => UserRole::COORDINADOR_SECTOR,
        ]);

        $gestor = User::factory()->create([
            'role' => UserRole::GESTOR_SECCIONAL,
        ]);

        $this->seed(ListaNominalPermissionSeeder::class);
        $coordinador->refresh();
        $gestor->refresh();

        foreach ([$coordinador, $gestor] as $user) {
            // Debe poder VER
            $this->assertTrue($user->can(ListaNominalPermission::VER->value));

            // NO debe poder CREAR, ACTIVAR, EDITAR o ELIMINAR
            $this->assertFalse($user->can(ListaNominalPermission::CREAR->value));
            $this->assertFalse($user->can(ListaNominalPermission::ACTIVAR->value));
            $this->assertFalse($user->can(ListaNominalPermission::EDITAR->value));
            $this->assertFalse($user->can(ListaNominalPermission::ELIMINAR->value));
        }
    }

    /**
     * 4. Verifica que los roles operativos inferiores no tengan acceso a Lista Nominal.
     */
    public function test_subordinate_roles_have_no_lista_nominal_permissions(): void
    {
        $subordinateRoles = [
            UserRole::PRESIDENTE_COMITE,
            UserRole::INTEGRANTE_COMITE,
            UserRole::DESDOBLE,
        ];

        $users = [];
        foreach ($subordinateRoles as $roleEnum) {
            $users[] = User::factory()->create(['role' => $roleEnum]);
        }

        $this->seed(ListaNominalPermissionSeeder::class);

        foreach ($users as $user) {
            $user->refresh();
            $this->assertTrue($user->hasRole($user->role->value));

            foreach (ListaNominalPermission::cases() as $permission) {
                $this->assertFalse(
                    $user->can($permission->value),
                    "El rol {$user->role->value} no debería tener el permiso {$permission->value}"
                );
            }
        }
    }

    /**
     * 5. Verifica que el seeder sea idempotente y no duplique registros al correrse 2 veces.
     */
    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ListaNominalPermissionSeeder::class);
        $this->seed(ListaNominalPermissionSeeder::class);

        $this->assertEquals(
            count(ListaNominalPermission::cases()),
            Permission::count(),
            'Los permisos no deben duplicarse al volver a ejecutar el seeder.'
        );
    }

    /**
     * 6. Verifica la protección de rutas mediante el middleware 'permission'.
     */
    public function test_permission_middleware_protects_routes_correctly(): void
    {
        // Rutas temporales de prueba para validar el middleware registrado en bootstrap/app.php
        Route::middleware(['web', 'auth', 'permission:' . ListaNominalPermission::VER->value])
            ->get('/test-route-ver', fn() => response('ok_ver', 200));

        Route::middleware(['web', 'auth', 'permission:' . ListaNominalPermission::CREAR->value])
            ->post('/test-route-crear', fn() => response('ok_crear', 200));

        $admin = User::factory()->create(['role' => UserRole::ADMINISTRADOR]);
        $gestor = User::factory()->create(['role' => UserRole::GESTOR_SECCIONAL]);

        $this->seed(ListaNominalPermissionSeeder::class);
        $admin->refresh();
        $gestor->refresh();

        // 1. Gestor puede acceder a VER (200), pero no a CREAR (403)
        $this->actingAs($gestor)
            ->get('/test-route-ver')
            ->assertStatus(200);

        $this->actingAs($gestor)
            ->post('/test-route-crear')
            ->assertStatus(403);

        // 2. Administrador puede acceder a ambas (200)
        $this->actingAs($admin)
            ->get('/test-route-ver')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->post('/test-route-crear')
            ->assertStatus(200);
    }
}
