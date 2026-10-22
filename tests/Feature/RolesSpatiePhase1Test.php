<?php

namespace Tests\Feature;

use App\Enums\ListaNominalPermission;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ListaNominalPermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesSpatiePhase1Test extends TestCase
{
    use RefreshDatabase;

    protected string $migrationPath = 'database/migrations/2026_10_19_133939_migrate_users_roles_to_spatie_and_drop_role_column.php';

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * 1. Spatie: verifica el orden cronológico de las migraciones.
     */
    public function test_spatie_permission_migration_runs_before_role_migration(): void
    {
        $files = collect( scandir(database_path('migrations')) )
            ->filter(fn (string $f) => str_ends_with($f, '.php'))
            ->values();

        $spatieIndex = $files->search(fn (string $f) => str_contains($f, 'create_permission_tables'));
        $phase1Index = $files->search(fn (string $f) => str_contains($f, 'migrate_users_roles_to_spatie_and_drop_role_column'));

        $this->assertNotFalse($spatieIndex, 'Debe existir la migración create_permission_tables.');
        $this->assertNotFalse($phase1Index, 'Debe existir la migración migrate_users_roles_to_spatie_and_drop_role_column.');
        $this->assertLessThan(
            $phase1Index,
            $spatieIndex,
            'La migración de tablas de Spatie debe ejecutarse antes que la migración de roles de usuarios.'
        );
    }

    /**
     * 2. Spatie: valida la existencia de las tablas de Spatie antes de migrar o eliminar users.role.
     */
    public function test_migration_validates_spatie_tables_existence_before_dropping_role_column(): void
    {
        $migration = require base_path($this->migrationPath);

        // Recrear columna role para simular estado previo
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('Gestor seccional')->after('email');
            });
        }

        // Eliminar temporalmente model_has_roles para simular tablas de Spatie faltantes
        Schema::dropIfExists('model_has_roles');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage("La tabla de Spatie 'model_has_roles' no existe");
            $migration->up();
        } finally {
            // Confirmar que la columna users.role NO se eliminó cuando faltaba la tabla de Spatie
            $this->assertTrue(Schema::hasColumn('users', 'role'));
        }
    }

    /**
     * 3. Migración: evita asignar roles por defecto a valores desconocidos y garantiza que no se pierdan datos.
     */
    public function test_migration_rejects_unknown_roles_without_assigning_default_or_losing_data(): void
    {
        $migration = require base_path($this->migrationPath);

        // Volver al estado previo a la migración (con columna users.role)
        $migration->down();
        $this->assertTrue(Schema::hasColumn('users', 'role'));

        $validUserId = DB::table('users')->insertGetId([
            'name'       => 'Usuario Conocido',
            'email'      => 'conocido@example.com',
            'password'   => Hash::make('password'),
            'role'       => 'Supervisor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unknownUserId = DB::table('users')->insertGetId([
            'name'       => 'Usuario Rol Desconocido',
            'email'      => 'desconocido@example.com',
            'password'   => Hash::make('password'),
            'role'       => 'Rol Inexistente XYZ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rolesBefore = DB::table('roles')->orderBy('id')->get()->toArray();
        $assignmentsBefore = DB::table('model_has_roles')->orderBy('model_id')->get()->toArray();

        try {
            $migration->up();
            $this->fail('La migración debió abortar al encontrar un rol desconocido.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("usuario ID {$unknownUserId}", $e->getMessage());
            $this->assertStringContainsString("'Rol Inexistente XYZ'", $e->getMessage());
        }

        // La columna users.role debe seguir intacta y con los datos originales sin pérdida
        $this->assertTrue(Schema::hasColumn('users', 'role'));
        $this->assertEquals('Supervisor', DB::table('users')->where('id', $validUserId)->value('role'));
        $this->assertEquals('Rol Inexistente XYZ', DB::table('users')->where('id', $unknownUserId)->value('role'));

        // El usuario con rol desconocido NO debe haber recibido 'Gestor seccional' por defecto
        $this->assertDatabaseMissing('model_has_roles', [
            'model_type' => User::class,
            'model_id'   => $unknownUserId,
        ]);
        $this->assertEquals($rolesBefore, DB::table('roles')->orderBy('id')->get()->toArray());
        $this->assertEquals($assignmentsBefore, DB::table('model_has_roles')->orderBy('model_id')->get()->toArray());
    }

    /**
     * 4. Migración y Rollback: conserva datos, registra auditoría y recupera exactamente los roles originales en down().
     */
    public function test_migration_and_rollback_preserve_exact_original_roles_and_audit_log(): void
    {
        $migration = require base_path($this->migrationPath);

        // Volver al estado con columna users.role
        $migration->down();
        $this->assertTrue(Schema::hasColumn('users', 'role'));

        // Crear un rol personalizado en Spatie para verificar que también se conserva si existe
        Role::firstOrCreate(['name' => 'Auditor Especial', 'guard_name' => 'web']);

        $originalRolesByEmail = [
            'admin_legacy@example.com'       => 'admin',
            'supervisor_legacy@example.com'  => 'Supervisor',
            'coord_sector@example.com'       => 'Coordinador de sector',
            'coord_territorial@example.com'  => 'Coordinador territorial',
            'capturista_legacy@example.com'  => 'Capturista',
            'gestor@example.com'             => 'Gestor seccional',
            'presidente@example.com'         => 'Presidente de comité',
            'integrante@example.com'         => 'Integrante de comité',
            'desdoble_legacy@example.com'    => 'Desdoble',
            'afines@example.com'             => 'Afines',
            'custom_auditor@example.com'     => 'Auditor Especial',
        ];

        $userIdsByEmail = [];
        foreach ($originalRolesByEmail as $email => $originalRole) {
            $userIdsByEmail[$email] = DB::table('users')->insertGetId([
                'name'       => "User {$originalRole}",
                'email'      => $email,
                'password'   => Hash::make('password'),
                'role'       => $originalRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ejecutar migración up()
        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'role'));

        // Verificar mapeo en Spatie
        $this->assertTrue(User::find($userIdsByEmail['admin_legacy@example.com'])->hasRole('Administrador'));
        $this->assertTrue(User::find($userIdsByEmail['supervisor_legacy@example.com'])->hasRole('Coordinador territorial'));
        $this->assertTrue(User::find($userIdsByEmail['coord_sector@example.com'])->hasRole('Coordinador territorial'));
        $this->assertTrue(User::find($userIdsByEmail['coord_territorial@example.com'])->hasRole('Coordinador territorial'));
        $this->assertTrue(User::find($userIdsByEmail['capturista_legacy@example.com'])->hasRole('Gestor seccional'));
        $this->assertTrue(User::find($userIdsByEmail['gestor@example.com'])->hasRole('Gestor seccional'));
        $this->assertTrue(User::find($userIdsByEmail['presidente@example.com'])->hasRole('Presidente de comité'));
        $this->assertTrue(User::find($userIdsByEmail['integrante@example.com'])->hasRole('Integrante de comité'));
        $this->assertTrue(User::find($userIdsByEmail['desdoble_legacy@example.com'])->hasRole('Afines'));
        $this->assertTrue(User::find($userIdsByEmail['afines@example.com'])->hasRole('Afines'));
        $this->assertTrue(User::find($userIdsByEmail['custom_auditor@example.com'])->hasRole('Auditor Especial'));

        // Verificar que la auditoría registró la normalización de roles históricos (ej. Supervisor -> Coordinador territorial)
        $auditSupervisor = Activity::where('subject_type', User::class)
            ->where('subject_id', $userIdsByEmail['supervisor_legacy@example.com'])
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditSupervisor);
        $this->assertEquals('Supervisor', $auditSupervisor->properties['old']['role'] ?? null);
        $this->assertEquals('Coordinador territorial', $auditSupervisor->properties['attributes']['role'] ?? null);

        // Ejecutar rollback down() y comprobar que CADA usuario recupera su rol original exacto
        $migration->down();

        $this->assertTrue(Schema::hasColumn('users', 'role'));

        foreach ($originalRolesByEmail as $email => $expectedOriginalRole) {
            $restoredRole = DB::table('users')->where('id', $userIdsByEmail[$email])->value('role');
            $this->assertEquals(
                $expectedOriginalRole,
                $restoredRole,
                "El rollback debió restaurar el rol original '{$expectedOriginalRole}' para {$email}."
            );
        }

        // Volver a aplicar up() para dejar el esquema limpio
        $migration->up();
        $this->assertFalse(Schema::hasColumn('users', 'role'));
    }

    /**
     * 5. Auditoría: conserva el registro de creación y cambios de roles en User.php.
     */
    public function test_user_model_logs_role_creation_and_role_updates_in_activity_log(): void
    {
        $this->seed(ListaNominalPermissionSeeder::class);

        // 1. Creación de usuario registra el rol en activity_log
        $user = User::create([
            'name'     => 'Brigadista Auditado',
            'email'    => 'auditado@example.com',
            'password' => Hash::make('password123'),
            'role'     => UserRole::GESTOR_SECCIONAL,
        ]);

        $createdLog = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($createdLog, 'Debe existir un registro de auditoría al crear el usuario.');
        $this->assertEquals(UserRole::GESTOR_SECCIONAL->value, $createdLog->properties['attributes']['role'] ?? null);

        // 2. Cambio únicamente de rol mediante update(['role' => ...]) sin columna física users.role
        $user->update(['role' => UserRole::COORDINADOR_TERRITORIAL]);

        $roleOnlyUpdateLog = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($roleOnlyUpdateLog, 'Debe registrarse auditoría cuando cambia únicamente el rol.');
        $this->assertEquals(UserRole::GESTOR_SECCIONAL->value, $roleOnlyUpdateLog->properties['old']['role'] ?? null);
        $this->assertEquals(UserRole::COORDINADOR_TERRITORIAL->value, $roleOnlyUpdateLog->properties['attributes']['role'] ?? null);

        // 3. Cambio simultáneo de nombre y rol genera un solo log con ambos cambios
        $countBefore = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->count();

        $user->update([
            'name' => 'Coordinador Renombrado',
            'role' => UserRole::ADMINISTRADOR,
        ]);

        $countAfter = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->count();

        $this->assertEquals($countBefore + 1, $countAfter, 'Debe generarse un único registro updated cuando cambian nombre y rol.');

        $combinedUpdateLog = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertEquals('Brigadista Auditado', $combinedUpdateLog->properties['old']['name'] ?? null);
        $this->assertEquals('Coordinador Renombrado', $combinedUpdateLog->properties['attributes']['name'] ?? null);
        $this->assertEquals(UserRole::COORDINADOR_TERRITORIAL->value, $combinedUpdateLog->properties['old']['role'] ?? null);
        $this->assertEquals(UserRole::ADMINISTRADOR->value, $combinedUpdateLog->properties['attributes']['role'] ?? null);

        // 4. Cambio directo mediante syncRoles() también queda auditado
        $user->syncRoles([UserRole::PRESIDENTE_COMITE->value]);

        $syncRolesLog = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertEquals(UserRole::ADMINISTRADOR->value, $syncRolesLog->properties['old']['role'] ?? null);
        $this->assertEquals(UserRole::PRESIDENTE_COMITE->value, $syncRolesLog->properties['attributes']['role'] ?? null);
    }

    /**
     * 6. Compatibilidad: valida los accesores y mutadores de User.php y su serialización.
     */
    public function test_user_role_accessors_mutators_and_serialization_compatibility(): void
    {
        $this->seed(ListaNominalPermissionSeeder::class);

        // Asignación con Enum
        $user = User::factory()->create(['role' => UserRole::ADMINISTRADOR]);
        $this->assertSame(UserRole::ADMINISTRADOR, $user->role);
        $this->assertTrue($user->hasRole('Administrador'));

        // Asignación con alias histórico ('Coordinador de sector', 'Supervisor', 'Capturista', 'Desdoble')
        $user->role = 'Supervisor';
        $this->assertSame(UserRole::COORDINADOR_TERRITORIAL, $user->role);
        $user->save();
        $this->assertSame(UserRole::COORDINADOR_TERRITORIAL, $user->fresh()->role);
        $this->assertTrue($user->fresh()->hasRole('Coordinador territorial'));

        $user->update(['role' => 'Desdoble']);
        $this->assertSame(UserRole::AFINES, $user->fresh()->role);
        $this->assertTrue($user->fresh()->hasRole('Afines'));

        // Asignación de rol personalizado en Spatie retorna el nombre en string
        $user->update(['role' => 'Observador Electoral']);
        $this->assertSame('Observador Electoral', $user->fresh()->role);
        $this->assertTrue($user->fresh()->hasRole('Observador Electoral'));

        // Serialización a array/JSON incluye 'role'
        $user->update(['role' => UserRole::GESTOR_SECCIONAL]);
        $array = $user->fresh()->toArray();
        $this->assertArrayHasKey('role', $array);
        $this->assertEquals(UserRole::GESTOR_SECCIONAL->value, $array['role'] instanceof \BackedEnum ? $array['role']->value : $array['role']);

        // Remover rol retorna null
        $user->update(['role' => null]);
        $this->assertNull($user->fresh()->role);
        $this->assertCount(0, $user->fresh()->roles);
    }

    /**
     * 7. Jerarquías y Permisos: prueba la cadena jerárquica completa de 6 niveles y validación en UserController.
     */
    public function test_roles_hierarchy_levels_and_user_controller_hierarchy_validation(): void
    {
        $this->seed(ListaNominalPermissionSeeder::class);

        // Verificar niveles del Enum (0 al 5) y parentLevel()
        $this->assertEquals(0, UserRole::ADMINISTRADOR->level());
        $this->assertNull(UserRole::ADMINISTRADOR->parentLevel());

        $this->assertEquals(1, UserRole::COORDINADOR_TERRITORIAL->level());
        $this->assertEquals(0, UserRole::COORDINADOR_TERRITORIAL->parentLevel());

        $this->assertEquals(2, UserRole::GESTOR_SECCIONAL->level());
        $this->assertEquals(1, UserRole::GESTOR_SECCIONAL->parentLevel());

        $this->assertEquals(3, UserRole::PRESIDENTE_COMITE->level());
        $this->assertEquals(2, UserRole::PRESIDENTE_COMITE->parentLevel());

        $this->assertEquals(4, UserRole::INTEGRANTE_COMITE->level());
        $this->assertEquals(3, UserRole::INTEGRANTE_COMITE->parentLevel());

        $this->assertEquals(5, UserRole::AFINES->level());
        $this->assertEquals(4, UserRole::AFINES->parentLevel());

        for ($level = 0; $level <= 5; $level++) {
            $this->assertNotNull(UserRole::fromLevel($level));
        }

        // Construir cadena jerárquica válida desde el controlador (Nivel 0 -> 1 -> 2 -> 3 -> 4 -> 5)
        $admin = User::factory()->create([
            'name'      => 'Admin Raíz',
            'role'      => UserRole::ADMINISTRADOR,
            'parent_id' => null,
        ]);

        // 1. Administrador NO puede tener superior
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Admin Inválido',
                'email'     => 'admin_invalido@example.com',
                'password'  => 'password123',
                'role'      => UserRole::ADMINISTRADOR->value,
                'parent_id' => $admin->id,
            ])
            ->assertSessionHasErrors(['parent_id']);

        // 2. Coordinador territorial requiere Administrador (nivel 0) como superior
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Coordinador Sin Padre',
                'email'     => 'coord_sin_padre@example.com',
                'password'  => 'password123',
                'role'      => UserRole::COORDINADOR_TERRITORIAL->value,
                'parent_id' => null,
            ])
            ->assertSessionHasErrors(['parent_id']);

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Coordinador Uno',
                'email'     => 'coord1@example.com',
                'password'  => 'password123',
                'role'      => UserRole::COORDINADOR_TERRITORIAL->value,
                'parent_id' => $admin->id,
            ])
            ->assertSessionHasNoErrors();

        $coordinador = User::where('email', 'coord1@example.com')->firstOrFail();
        $this->assertSame(UserRole::COORDINADOR_TERRITORIAL, $coordinador->role);
        $this->assertEquals($admin->id, $coordinador->parent_id);

        // 3. Gestor seccional no puede colgar directamente de Administrador (salto de nivel)
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Gestor Con Padre Incorrecto',
                'email'     => 'gestor_mal@example.com',
                'password'  => 'password123',
                'role'      => UserRole::GESTOR_SECCIONAL->value,
                'parent_id' => $admin->id,
            ])
            ->assertSessionHasErrors(['parent_id']);

        // Gestor seccional bajo Coordinador territorial -> válido
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Gestor Uno',
                'email'     => 'gestor1@example.com',
                'password'  => 'password123',
                'role'      => UserRole::GESTOR_SECCIONAL->value,
                'parent_id' => $coordinador->id,
            ])
            ->assertSessionHasNoErrors();

        $gestor = User::where('email', 'gestor1@example.com')->firstOrFail();

        // 4. Presidente de comité bajo Gestor seccional -> válido
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Presidente Uno',
                'email'     => 'presidente1@example.com',
                'password'  => 'password123',
                'role'      => UserRole::PRESIDENTE_COMITE->value,
                'parent_id' => $gestor->id,
            ])
            ->assertSessionHasNoErrors();

        $presidente = User::where('email', 'presidente1@example.com')->firstOrFail();

        // 5. Integrante de comité bajo Presidente de comité -> válido
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Integrante Uno',
                'email'     => 'integrante1@example.com',
                'password'  => 'password123',
                'role'      => UserRole::INTEGRANTE_COMITE->value,
                'parent_id' => $presidente->id,
            ])
            ->assertSessionHasNoErrors();

        $integrante = User::where('email', 'integrante1@example.com')->firstOrFail();

        // 6. Afines bajo Integrante de comité -> válido
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name'      => 'Afines Uno',
                'email'     => 'afines1@example.com',
                'password'  => 'password123',
                'role'      => UserRole::AFINES->value,
                'parent_id' => $integrante->id,
            ])
            ->assertSessionHasNoErrors();

        $afines = User::where('email', 'afines1@example.com')->firstOrFail();
        $this->assertSame(UserRole::AFINES, $afines->role);
        $this->assertEquals($integrante->id, $afines->parent_id);

        // 7. Vistas de listado (con filtro de rol) y organigrama funcionan sin columna física users.role
        $this->actingAs($admin)
            ->get(route('usuarios.index', ['role' => UserRole::GESTOR_SECCIONAL->value]))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('usuarios.organigrama'))
            ->assertStatus(200);
    }
}
