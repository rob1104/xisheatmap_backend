<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected string $backupTable = 'user_role_migration_backups';

    protected array $canonicalRoles = [
        'Administrador',
        'Coordinador territorial',
        'Gestor seccional',
        'Presidente de comité',
        'Integrante de comité',
        'Afines',
    ];

    /**
     * Valida que las tablas requeridas de Spatie Permission y users existan
     * antes de ejecutar cualquier cambio de esquema o migración de datos.
     *
     * @return array{roles: string, permissions: string, model_has_roles: string, model_has_permissions: string, role_has_permissions: string, model_morph_key: string, role_pivot_key: string}
     */
    protected function validateAndResolveSpatieConfig(): array
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        if (empty($tableNames) || ! is_array($tableNames)) {
            throw new \RuntimeException(
                'Configuración de Spatie Permission (config/permission.php) no cargada. Ejecuta primero la configuración y migración de Spatie.'
            );
        }

        $resolved = [
            'roles'                 => $tableNames['roles'] ?? 'roles',
            'permissions'           => $tableNames['permissions'] ?? 'permissions',
            'model_has_roles'       => $tableNames['model_has_roles'] ?? 'model_has_roles',
            'model_has_permissions' => $tableNames['model_has_permissions'] ?? 'model_has_permissions',
            'role_has_permissions'  => $tableNames['role_has_permissions'] ?? 'role_has_permissions',
            'model_morph_key'       => $columnNames['model_morph_key'] ?? 'model_id',
            'role_pivot_key'        => $columnNames['role_pivot_key'] ?? 'role_id',
        ];

        if (! Schema::hasTable('users')) {
            throw new \RuntimeException("La tabla 'users' no existe. Verifica el orden de las migraciones.");
        }

        foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $key) {
            $table = $resolved[$key];
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException(
                    "La tabla de Spatie '{$table}' no existe. Verifica el orden de las migraciones y asegúrate de que create_permission_tables se ejecute antes."
                );
            }
        }

        return $resolved;
    }

    /**
     * Resuelve únicamente roles canónicos o alias históricos conocidos.
     * Retorna null si el valor es desconocido para evitar asignar un rol por defecto.
     */
    protected function resolveKnownRole(?string $rawRole): ?string
    {
        if ($rawRole === null) {
            return null;
        }

        $trimmed = trim($rawRole);
        if ($trimmed === '') {
            return null;
        }

        return match ($trimmed) {
            'Administrador', 'administrador', 'admin'            => UserRole::ADMINISTRADOR->value,
            'Coordinador territorial', 'coordinador territorial',
            'Coordinador de sector', 'coordinador de sector',
            'Supervisor', 'supervisor'                           => UserRole::COORDINADOR_TERRITORIAL->value,
            'Gestor seccional', 'gestor seccional',
            'Capturista', 'capturista'                           => UserRole::GESTOR_SECCIONAL->value,
            'Presidente de comité', 'presidente de comité'       => UserRole::PRESIDENTE_COMITE->value,
            'Integrante de comité', 'integrante de comité'       => UserRole::INTEGRANTE_COMITE->value,
            'Afines', 'afines', 'Desdoble', 'desdoble'           => UserRole::AFINES->value,
            default                                              => null,
        };
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $spatie = $this->validateAndResolveSpatieConfig();
        $rolesTable = $spatie['roles'];
        $modelHasRolesTable = $spatie['model_has_roles'];
        $modelMorphKey = $spatie['model_morph_key'];
        $rolePivotKey = $spatie['role_pivot_key'];

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Asegurar roles canónicos en Spatie
        $roleMap = [];
        foreach ($this->canonicalRoles as $name) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $roleMap[$name] = $role->id;
        }

        // Incluir roles personalizados ya existentes en Spatie para no perderlos si un usuario ya los usa
        $existingRoles = DB::table($rolesTable)
            ->where('guard_name', 'web')
            ->pluck('id', 'name')
            ->all();
        $roleMap = array_replace($existingRoles, $roleMap);

        // 2. Normalizar nombres históricos y migrar a Spatie sin asignar roles por defecto a valores desconocidos
        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->select('id', 'role')->get();

            // Pre-validación completa antes de modificar datos o eliminar la columna users.role
            $migrationPlan = [];
            foreach ($users as $user) {
                $rawRole = $user->role !== null ? trim((string) $user->role) : '';
                $targetRole = $this->resolveKnownRole($rawRole);

                // Si no es un alias canónico pero coincide con un rol ya registrado en Spatie, conservarlo
                if ($targetRole === null && $rawRole !== '' && isset($roleMap[$rawRole])) {
                    $targetRole = $rawRole;
                }

                // Si users.role está vacío/nulo, verificar si ya tiene un rol asignado en Spatie
                if ($targetRole === null && $rawRole === '') {
                    $existingSpatieRole = DB::table($modelHasRolesTable)
                        ->join($rolesTable, "{$modelHasRolesTable}.{$rolePivotKey}", '=', "{$rolesTable}.id")
                        ->where("{$modelHasRolesTable}.model_type", User::class)
                        ->where("{$modelHasRolesTable}.{$modelMorphKey}", $user->id)
                        ->value("{$rolesTable}.name");

                    if ($existingSpatieRole !== null) {
                        $normalizedExisting = $this->resolveKnownRole($existingSpatieRole) ?? $existingSpatieRole;
                        if (! isset($roleMap[$normalizedExisting])) {
                            $roleObj = Role::firstOrCreate(['name' => $normalizedExisting, 'guard_name' => 'web']);
                            $roleMap[$normalizedExisting] = $roleObj->id;
                        }
                        $targetRole = $normalizedExisting;
                    }
                }

                if ($targetRole === null) {
                    $displayValue = $rawRole === '' ? 'NULL/vacío' : "'{$rawRole}'";
                    throw new \RuntimeException(
                        "Migración abortada para evitar pérdida de datos: el usuario ID {$user->id} tiene un rol desconocido o inválido ({$displayValue})."
                    );
                }

                $migrationPlan[] = [
                    'user_id'       => $user->id,
                    'original_role' => $user->role,
                    'target_role'   => $targetRole,
                    'role_id'       => $roleMap[$targetRole],
                ];
            }

            // Crear tabla de respaldo para garantizar rollback exacto de roles originales
            if (! Schema::hasTable($this->backupTable)) {
                Schema::create($this->backupTable, function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->primary();
                    $table->string('original_role')->nullable();
                    $table->string('migrated_role');
                    $table->timestamp('created_at')->nullable();
                });
            }

            $activityTable = config('activitylog.table_name', 'activity_log');
            $hasActivityTable = Schema::hasTable($activityTable);
            $now = now();

            DB::transaction(function () use ($migrationPlan, $modelHasRolesTable, $modelMorphKey, $rolePivotKey, $hasActivityTable, $activityTable, $now) {
                foreach ($migrationPlan as $item) {
                    // Guardar respaldo del rol original exacto
                    DB::table($this->backupTable)->updateOrInsert(
                        ['user_id' => $item['user_id']],
                        [
                            'original_role' => $item['original_role'],
                            'migrated_role' => $item['target_role'],
                            'created_at'    => $now,
                        ]
                    );

                    // Limpiar cualquier relación residual previa en model_has_roles para este usuario
                    DB::table($modelHasRolesTable)
                        ->where('model_type', User::class)
                        ->where($modelMorphKey, $item['user_id'])
                        ->delete();

                    // Insertar exactamente un único rol en Spatie
                    DB::table($modelHasRolesTable)->insert([
                        $rolePivotKey    => $item['role_id'],
                        'model_type'     => User::class,
                        $modelMorphKey   => $item['user_id'],
                    ]);

                    // Conservar registro de auditoría si el rol fue normalizado o migrado
                    if ($hasActivityTable && $item['original_role'] !== $item['target_role']) {
                        DB::table($activityTable)->insert([
                            'log_name'     => config('activitylog.default_log_name', 'default'),
                            'description'  => 'Usuario updated',
                            'subject_type' => User::class,
                            'subject_id'   => $item['user_id'],
                            'event'        => 'updated',
                            'causer_type'  => null,
                            'causer_id'    => null,
                            'properties'   => json_encode([
                                'attributes' => ['role' => $item['target_role']],
                                'old'        => ['role' => $item['original_role']],
                            ], JSON_UNESCAPED_UNICODE),
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]);
                    }
                }
            });

            // 3. Validación de integridad: Cero huérfanos
            $totalUsers = DB::table('users')->count();
            $totalAssigned = DB::table($modelHasRolesTable)->where('model_type', User::class)->count();

            if ($totalUsers !== $totalAssigned) {
                throw new \RuntimeException("Falla de integridad: {$totalUsers} usuarios en total pero {$totalAssigned} asignados en Spatie.");
            }

            // 4. Eliminación definitiva de la columna redundante users.role
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            // Limpiar roles con nomenclatura antigua si quedaron sin asignaciones
            DB::table($rolesTable)
                ->whereIn('name', ['Coordinador de sector', 'Desdoble'])
                ->whereNotExists(function ($query) use ($modelHasRolesTable, $rolesTable, $rolePivotKey) {
                    $query->select(DB::raw(1))
                        ->from($modelHasRolesTable)
                        ->whereColumn("{$modelHasRolesTable}.{$rolePivotKey}", "{$rolesTable}.id");
                })
                ->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * Reversión segura:
     * 1. Restaura los valores originales exactos desde la tabla de respaldo `user_role_migration_backups`
     *    generada en `up()`, preservando alias históricos ('Supervisor', 'Coordinador de sector', 'Capturista',
     *    'Desdoble', 'admin', etc.) y roles personalizados.
     * 2. Si un usuario fue creado después de la migración o su rol cambió en Spatie, asigna su rol actual en Spatie.
     * 3. Si la tabla de respaldo `user_role_migration_backups` fue eliminada manualmente en producción,
     *    la distinción entre alias históricos colapsados es irreversible sin un respaldo previo de base de datos.
     */
    public function down(): void
    {
        $spatie = $this->validateAndResolveSpatieConfig();
        $rolesTable = $spatie['roles'];
        $modelHasRolesTable = $spatie['model_has_roles'];
        $modelMorphKey = $spatie['model_morph_key'];
        $rolePivotKey = $spatie['role_pivot_key'];

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Recrear columna users.role si no existe
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('Gestor seccional')->after('email');
            });
        }

        // 2. Obtener respaldos originales si existen
        $backups = Schema::hasTable($this->backupTable)
            ? DB::table($this->backupTable)->get()->keyBy('user_id')
            : collect();

        // 3. Obtener asignaciones actuales en Spatie
        $assignments = DB::table($modelHasRolesTable)
            ->join($rolesTable, "{$modelHasRolesTable}.{$rolePivotKey}", '=', "{$rolesTable}.id")
            ->where("{$modelHasRolesTable}.model_type", User::class)
            ->select("{$modelHasRolesTable}.{$modelMorphKey} as model_id", "{$rolesTable}.name")
            ->get()
            ->keyBy('model_id');

        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            $backup = $backups->get($userId);
            $currentSpatieRole = $assignments->get($userId)?->name;

            if ($backup !== null && $backup->original_role !== null && $backup->original_role !== '') {
                // Si el usuario no cambió de rol en Spatie después de la migración, restauramos su rol original exacto
                if ($currentSpatieRole === null || $currentSpatieRole === $backup->migrated_role) {
                    $restoredRole = $backup->original_role;
                } else {
                    $restoredRole = $currentSpatieRole;
                }
            } elseif ($currentSpatieRole !== null) {
                $restoredRole = $currentSpatieRole;
            } else {
                continue;
            }

            DB::table('users')->where('id', $userId)->update(['role' => $restoredRole]);
        }

        if (Schema::hasTable($this->backupTable)) {
            Schema::dropIfExists($this->backupTable);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
