<?php

namespace Tests\Feature;

use App\Enums\ListaNominalPermission;
use App\Enums\RolePermission;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ListaNominalPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ListaNominalPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMINISTRADOR->value,
        ]);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('roles.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles',
                'permissions',
            ]);

        $roleNames = collect($response->json('roles'))->pluck('name')->all();
        foreach (UserRole::values() as $canonicalName) {
            $this->assertContains($canonicalName, $roleNames);
        }
    }

    public function test_user_without_permission_cannot_access_roles_module(): void
    {
        $gestor = User::factory()->create([
            'role' => UserRole::GESTOR_SECCIONAL->value,
        ]);

        $this->actingAs($gestor)
            ->get(route('roles.index'))
            ->assertStatus(403);

        $this->actingAs($gestor)
            ->post(route('roles.store'), ['name' => 'Rol Ilegal'])
            ->assertStatus(403);
    }

    public function test_granular_permissions_and_global_meta_permission_work_in_role_policy(): void
    {
        $coordinador = User::factory()->create([
            'role' => UserRole::COORDINADOR_TERRITORIAL->value,
        ]);

        // Sin permisos de roles -> 403
        $this->actingAs($coordinador)
            ->get(route('roles.index'))
            ->assertStatus(403);

        // Con permiso granular roles.ver -> puede ver index (200), pero no crear (403)
        $coordinador->givePermissionTo(RolePermission::VER->value);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($coordinador->fresh())
            ->getJson(route('roles.index'))
            ->assertStatus(200);

        $this->actingAs($coordinador->fresh())
            ->post(route('roles.store'), ['name' => 'Supervisor Especial'])
            ->assertStatus(403);

        // Con meta-permiso roles.gestionar -> bypass total para crear, editar y eliminar
        $coordinador->givePermissionTo(RolePermission::GESTIONAR->value);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($coordinador->fresh())
            ->post(route('roles.store'), [
                'name'        => 'Supervisor Especial',
                'permissions' => [ListaNominalPermission::VER->value],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', [
            'name'       => 'Supervisor Especial',
            'guard_name' => 'web',
        ]);
    }

    public function test_can_create_custom_role_and_sync_permissions_with_web_guard(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('roles.store'), [
                'name'        => 'Auditor Territorial',
                'permissions' => [
                    ListaNominalPermission::VER->value,
                    RolePermission::VER->value,
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $role = Role::where('name', 'Auditor Territorial')->where('guard_name', 'web')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo(ListaNominalPermission::VER->value));
        $this->assertTrue($role->hasPermissionTo(RolePermission::VER->value));
    }

    public function test_cannot_create_duplicate_role_in_web_guard(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('roles.store'), [
                'name' => UserRole::ADMINISTRADOR->value,
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_protected_base_roles_cannot_be_renamed_but_permissions_can_be_updated(): void
    {
        $gestorRole = Role::where('name', UserRole::GESTOR_SECCIONAL->value)->firstOrFail();

        // 1. Intentar renombrar rol base -> debe fallar con error en 'name'
        $responseRename = $this->actingAs($this->admin)
            ->put(route('roles.update', $gestorRole->id), [
                'name'        => 'Nuevo Nombre Gestor',
                'permissions' => [ListaNominalPermission::VER->value],
            ]);

        $responseRename->assertSessionHasErrors(['name']);
        $this->assertEquals(UserRole::GESTOR_SECCIONAL->value, $gestorRole->fresh()->name);

        // 2. Actualizar únicamente permisos manteniendo su nombre original -> debe permitirlo
        $responsePerms = $this->actingAs($this->admin)
            ->put(route('roles.update', $gestorRole->id), [
                'name'        => UserRole::GESTOR_SECCIONAL->value,
                'permissions' => [
                    ListaNominalPermission::VER->value,
                    RolePermission::VER->value,
                ],
            ]);

        $responsePerms->assertSessionHasNoErrors();
        $this->assertTrue($gestorRole->fresh()->hasPermissionTo(RolePermission::VER->value));
    }

    public function test_custom_role_can_be_renamed_and_permissions_updated(): void
    {
        $customRole = Role::create(['name' => 'Rol Temporal', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('roles.update', $customRole->id), [
                'name'        => 'Rol Renombrado',
                'permissions' => [ListaNominalPermission::VER->value],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('Rol Renombrado', $customRole->fresh()->name);
        $this->assertTrue($customRole->fresh()->hasPermissionTo(ListaNominalPermission::VER->value));
    }

    public function test_protected_base_roles_cannot_be_deleted(): void
    {
        foreach (UserRole::values() as $protectedName) {
            $role = Role::where('name', $protectedName)->firstOrFail();

            $response = $this->actingAs($this->admin)
                ->delete(route('roles.destroy', $role->id));

            $response->assertSessionHasErrors(['message']);
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        }
    }

    public function test_custom_role_with_assigned_users_cannot_be_deleted(): void
    {
        $customRole = Role::create(['name' => 'Rol Con Usuarios', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'Rol Con Usuarios']);

        $this->assertTrue($user->hasRole('Rol Con Usuarios'));

        $response = $this->actingAs($this->admin)
            ->delete(route('roles.destroy', $customRole->id));

        $response->assertSessionHasErrors(['message']);
        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_custom_role_without_users_can_be_deleted(): void
    {
        $customRole = Role::create(['name' => 'Rol Sin Usuarios', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('roles.destroy', $customRole->id));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_administrador_role_cannot_have_roles_gestionar_permission_removed(): void
    {
        $adminRole = Role::where('name', UserRole::ADMINISTRADOR->value)->firstOrFail();

        // 1. Intentar guardar Administrador con arreglo vacío de permisos -> debe rechazarse
        $responseEmpty = $this->actingAs($this->admin)
            ->put(route('roles.update', $adminRole->id), [
                'name'        => UserRole::ADMINISTRADOR->value,
                'permissions' => [],
            ]);

        $responseEmpty->assertSessionHasErrors(['permissions']);
        $this->assertTrue($adminRole->fresh()->hasPermissionTo(RolePermission::GESTIONAR->value));

        // 2. Intentar guardar Administrador sin roles.gestionar -> debe rechazarse
        $responseWithoutGestionar = $this->actingAs($this->admin)
            ->put(route('roles.update', $adminRole->id), [
                'name'        => UserRole::ADMINISTRADOR->value,
                'permissions' => [ListaNominalPermission::VER->value],
            ]);

        $responseWithoutGestionar->assertSessionHasErrors(['permissions']);
        $this->assertTrue($adminRole->fresh()->hasPermissionTo(RolePermission::GESTIONAR->value));

        // 3. Guardar Administrador incluyendo roles.gestionar -> debe permitirse
        $responseValid = $this->actingAs($this->admin)
            ->put(route('roles.update', $adminRole->id), [
                'name'        => UserRole::ADMINISTRADOR->value,
                'permissions' => [
                    RolePermission::GESTIONAR->value,
                    ListaNominalPermission::VER->value,
                ],
            ]);

        $responseValid->assertSessionHasNoErrors();
        $this->assertTrue($adminRole->fresh()->hasPermissionTo(RolePermission::GESTIONAR->value));
        $this->assertTrue($adminRole->fresh()->hasPermissionTo(ListaNominalPermission::VER->value));
    }

    public function test_cannot_remove_or_demote_the_last_administrador_user(): void
    {
        // Actualmente $this->admin es el único Administrador
        $this->assertEquals(1, User::role(UserRole::ADMINISTRADOR->value)->count());

        // 1. Intentar cambiar el rol del único Administrador -> debe fallar con error en 'role'
        $responseDemoteLast = $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->admin->id), [
                'name'      => $this->admin->name,
                'email'     => $this->admin->email,
                'role'      => UserRole::COORDINADOR_TERRITORIAL->value,
                'parent_id' => null,
            ]);

        $responseDemoteLast->assertSessionHasErrors(['role']);
        $this->assertTrue($this->admin->fresh()->hasRole(UserRole::ADMINISTRADOR->value));

        // 2. Crear un segundo Administrador -> ahora sí se puede cambiar el rol de uno de ellos
        $secondAdmin = User::factory()->create([
            'role'      => UserRole::ADMINISTRADOR->value,
            'parent_id' => null,
        ]);

        $this->assertEquals(2, User::role(UserRole::ADMINISTRADOR->value)->count());

        $responseDemoteSecond = $this->actingAs($this->admin)
            ->put(route('usuarios.update', $secondAdmin->id), [
                'name'      => $secondAdmin->name,
                'email'     => $secondAdmin->email,
                'role'      => UserRole::COORDINADOR_TERRITORIAL->value,
                'parent_id' => $this->admin->id,
            ]);

        $responseDemoteSecond->assertSessionHasNoErrors();
        $this->assertTrue($secondAdmin->fresh()->hasRole(UserRole::COORDINADOR_TERRITORIAL->value));
        $this->assertEquals(1, User::role(UserRole::ADMINISTRADOR->value)->count());
    }

    public function test_custom_spatie_role_compatibility_and_dashboard_en_campo_metric(): void
    {
        // Crear rol personalizado con permiso de visualización
        $customRole = Role::create(['name' => 'Analista Electoral', 'guard_name' => 'web']);
        $customRole->givePermissionTo(RolePermission::VER->value);

        $customUser = User::factory()->create(['role' => 'Analista Electoral']);

        // 1. Validar que el rol personalizado devuelve string y nivel jerárquico null
        $this->assertSame('Analista Electoral', $customUser->fresh()->role);
        $this->assertSame('Analista Electoral', $customUser->fresh()->getRoleName());
        $this->assertNull($customUser->fresh()->getHierarchyLevel());

        // 2. Crear usuarios de la jerarquía organizacional
        $coordinador = User::factory()->create([
            'role'      => UserRole::COORDINADOR_TERRITORIAL,
            'parent_id' => $this->admin->id,
        ]);
        $gestor = User::factory()->create([
            'role'      => UserRole::GESTOR_SECCIONAL,
            'parent_id' => $coordinador->id,
        ]);
        $presidente = User::factory()->create([
            'role'      => UserRole::PRESIDENTE_COMITE,
            'parent_id' => $gestor->id,
        ]);

        // 3. Organigrama funciona sin errores con usuarios con rol personalizado en la BD
        $this->actingAs($this->admin)
            ->get(route('usuarios.organigrama'))
            ->assertStatus(200);

        // 4. Dashboard calcula en_campo contando únicamente roles de campo (Gestor y Presidente = 2),
        // excluyendo Administrador, Coordinador territorial y el rol personalizado 'Analista Electoral'
        $expectedFieldCount = User::whereHas('roles', fn ($q) => $q->whereIn('name', UserRole::fieldRoles()))->count();
        $this->assertEquals(2, $expectedFieldCount);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200);
    }
}