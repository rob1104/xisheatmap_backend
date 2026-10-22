<?php

namespace Tests\Feature;

use App\Enums\ListaNominalPermission;
use App\Enums\RolePermission;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ListaNominalPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Módulos generales reservados a los roles operativos.
     *
     * @var array<string>
     */
    private const GENERAL_MODULES = [
        'expedientes.index',
        'tarjetas.index',
        'usuarios.index',
        'usuarios.organigrama',
        'apoyos.index',
        'casas-mesil.index',
        'heatmap.index',
        'logs.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ListaNominalPermissionSeeder::class);
    }

    private function userWithCustomRole(string $roleName, array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return User::factory()->create(['role' => $roleName]);
    }

    private function login(User $user)
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_operational_roles_can_log_in_and_use_general_modules(): void
    {
        foreach (UserRole::panelRoles() as $roleName) {
            $user = User::factory()->create(['role' => $roleName]);

            $this->login($user)->assertRedirect(route('dashboard', absolute: false));
            $this->assertAuthenticatedAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('auth.full_panel_access', true));
            $this->get(route('usuarios.index'))->assertOk();

            $this->post('/logout');
        }
    }

    public function test_custom_role_with_roles_ver_can_log_in_and_only_enter_roles_module(): void
    {
        $user = $this->userWithCustomRole('Auditor de roles', [RolePermission::VER->value]);

        $this->login($user);
        $this->assertAuthenticatedAs($user);

        // El resumen lo envía a su único módulo autorizado
        $this->get(route('dashboard'))->assertRedirect(route('roles.index'));

        $this->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Roles/Index')
                ->where('auth.full_panel_access', false));

        // roles.ver no concede escritura
        $this->post(route('roles.store'), ['name' => 'Rol no autorizado'])->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'Rol no autorizado']);

        foreach (self::GENERAL_MODULES as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
        $this->get(route('lista-nominal.index'))->assertForbidden();

        // Perfil propio sigue disponible
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_custom_role_with_roles_gestionar_can_manage_roles_but_not_users(): void
    {
        $user = $this->userWithCustomRole('Gestor de permisos', [RolePermission::GESTIONAR->value]);

        $this->login($user);
        $this->assertAuthenticatedAs($user);

        $this->post(route('roles.store'), ['name' => 'Rol creado por gestor'])->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'Rol creado por gestor', 'guard_name' => 'web']);

        $this->get(route('dashboard'))->assertRedirect(route('roles.index'));
        foreach (self::GENERAL_MODULES as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('usuarios.store'), [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMINISTRADOR->value,
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_custom_role_with_lista_nominal_ver_only_enters_lista_nominal(): void
    {
        $user = $this->userWithCustomRole('Consulta lista nominal', [ListaNominalPermission::VER->value]);

        $this->login($user);
        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('lista-nominal.index'));
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_custom_role_without_module_permissions_cannot_log_in_or_enter_panel(): void
    {
        $user = $this->userWithCustomRole('Rol sin permisos', []);

        $this->login($user)->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
    }

    public function test_custom_role_with_unrelated_permission_cannot_log_in(): void
    {
        Permission::firstOrCreate(['name' => 'reports.read', 'guard_name' => 'web']);
        $user = $this->userWithCustomRole('Rol con permiso ajeno', ['reports.read']);

        $this->login($user)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_operational_base_role_without_permissions_cannot_log_in(): void
    {
        $user = User::factory()->create(['role' => UserRole::INTEGRANTE_COMITE->value]);

        $this->login($user)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_operational_base_role_granted_roles_ver_only_enters_roles_module(): void
    {
        $role = Role::findByName(UserRole::AFINES->value, 'web');
        $role->givePermissionTo(RolePermission::VER->value);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create(['role' => UserRole::AFINES->value]);

        $this->login($user);
        $this->assertAuthenticatedAs($user);

        $this->get(route('roles.index'))->assertOk();
        $this->get(route('expedientes.index'))->assertForbidden();
    }
}
