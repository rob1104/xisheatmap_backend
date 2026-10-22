<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMINISTRADOR->value,
        ]);
        Role::firstOrCreate(['name' => 'Afines', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Rol personalizado', 'guard_name' => 'web']);
    }

    public function test_users_index_gets_role_options_and_user_roles_from_spatie(): void
    {
        $user = User::factory()->create(['role' => 'Rol personalizado']);

        $response = $this->actingAs($this->admin)->getJson(route('usuarios.index'));

        $response->assertOk();

        $this->assertContains('Rol personalizado', collect($response->json('roles'))->pluck('value')->all());
        $this->assertSame('Rol personalizado', collect($response->json('users.data'))->firstWhere('id', $user->id)['role']);
        $this->assertFalse(Schema::hasColumn('users', 'role'));
    }

    public function test_user_can_be_created_with_custom_spatie_role_and_independent_parent(): void
    {
        $parent = User::factory()->create(['role' => UserRole::AFINES->value]);

        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Integrante sin nivel',
            'email' => 'integrante@example.com',
            'password' => 'password-segura',
            'role' => 'Rol personalizado',
            'parent_id' => $parent->id,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'integrante@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Rol personalizado'));
        $this->assertSame($parent->id, $user->parent_id);
    }

    public function test_user_role_must_belong_to_the_web_guard(): void
    {
        Role::firstOrCreate(['name' => 'Rol de API', 'guard_name' => 'api']);

        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Usuario API',
            'email' => 'usuario-api@example.com',
            'password' => 'password-segura',
            'role' => 'Rol de API',
            'parent_id' => null,
        ])->assertSessionHasErrors('role');
    }

    public function test_user_cannot_be_own_parent_or_create_a_hierarchy_cycle(): void
    {
        $parent = User::factory()->create(['role' => UserRole::AFINES->value]);
        $child = User::factory()->create([
            'role' => UserRole::AFINES->value,
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $parent), $this->userData($parent, $parent->id))
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $parent), $this->userData($parent, $child->id))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_last_administrator_cannot_be_demoted_or_deleted(): void
    {
        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->admin), $this->userData($this->admin, null, 'Afines'))
            ->assertSessionHasErrors('role');

        $this->actingAs($this->admin)
            ->delete(route('usuarios.destroy', $this->admin))
            ->assertSessionHasErrors('message');

        $this->assertTrue($this->admin->fresh()->hasRole(UserRole::ADMINISTRADOR->value));
    }

    public function test_administrator_can_be_demoted_when_another_admin_remains(): void
    {
        $secondAdmin = User::factory()->create(['role' => UserRole::ADMINISTRADOR->value]);

        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $secondAdmin), $this->userData($secondAdmin, null, 'Afines'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($secondAdmin->fresh()->hasRole('Afines'));
        $this->assertTrue($this->admin->fresh()->hasRole(UserRole::ADMINISTRADOR->value));
    }

    public function test_admin_access_middleware_uses_spatie_role(): void
    {
        $this->actingAs($this->admin)->get(route('usuarios.index'))->assertOk();

        $customUser = User::factory()->create(['role' => 'Rol personalizado']);
        $this->actingAs($customUser)->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_organigrama_contract_exposes_hierarchy_by_parent_id_without_role_level(): void
    {
        $this->withoutVite();
        $child = User::factory()->create(['role' => 'Rol personalizado', 'parent_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get(route('usuarios.organigrama'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/OrgChart')
                ->has('users', 2)
                ->where('users', fn ($users) => collect($users)->every(
                    fn ($user) => array_keys($user) === ['id', 'name', 'role', 'parent_id', 'ines_count', 'created_at']
                ))
                ->where('users', fn ($users) => collect($users)->firstWhere('id', $child->id)['parent_id'] === $this->admin->id
                    && collect($users)->firstWhere('id', $child->id)['role'] === 'Rol personalizado'));
    }

    public function test_dashboard_en_campo_counts_only_field_roles(): void
    {
        $this->withoutVite();
        User::factory()->create(['role' => UserRole::COORDINADOR_TERRITORIAL->value]);
        $customUser = User::factory()->create(['role' => 'Rol personalizado']);
        $apiFieldRole = Role::firstOrCreate([
            'name' => UserRole::GESTOR_SECCIONAL->value,
            'guard_name' => 'api',
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $apiFieldRole->id,
            'model_type' => User::class,
            'model_id' => $customUser->id,
        ]);
        foreach (UserRole::fieldRoles() as $fieldRole) {
            User::factory()->create(['role' => $fieldRole]);
        }

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('kpis.en_campo', count(UserRole::fieldRoles())));
    }

    private function userData(User $user, ?int $parentId, string $role = UserRole::AFINES->value): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $role,
            'parent_id' => $parentId,
        ];
    }
}
