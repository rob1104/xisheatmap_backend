<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\ActivityLogStatus;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'parent_id', 'role', 'latitud_actual', 'longitud_actual', 'ultima_conexion_app'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasRoles {
        HasRoles::syncRoles as protected spatieSyncRoles;
        HasRoles::assignRole as protected spatieAssignRole;
    }

    /**
     * Incluir el atributo virtual 'role' en la serialización a array/JSON (Inertia, API, exportaciones).
     *
     * @var list<string>
     */
    protected $appends = ['role'];

    /**
     * Rol temporal en memoria para sincronizar con Spatie tras guardar el modelo.
     */
    protected ?string $pendingSpatieRole = null;
    protected bool $hasPendingSpatieRole = false;
    protected ?string $previousSpatieRole = null;
    protected bool $hasCapturedPreviousRole = false;
    protected bool $roleChangeLoggedInCycle = false;
    protected bool $isCreatingModel = false;
    protected bool $isInternalRoleSync = false;
    protected static bool $roleAuditPipeRegistered = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function ines()
    {
        return $this->hasMany(IneRecord::class, 'user_id');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Verifica que las tablas requeridas de Spatie Permission existan en la base de datos.
     */
    public static function hasSpatiePermissionTables(): bool
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        return Schema::hasTable($rolesTable) && Schema::hasTable($modelHasRolesTable);
    }

    /**
     * Obtiene el nombre del rol actual en Spatie (normalizado) directamente o desde la relación.
     */
    public function getCurrentSpatieRoleName(): ?string
    {
        if ($this->exists && static::hasSpatiePermissionTables()) {
            $roleName = $this->roles()->first()?->name;
            if ($roleName !== null) {
                return $this->normalizeRoleName($roleName);
            }
        }

        if (array_key_exists('role', $this->attributes) && $this->attributes['role'] !== null) {
            return $this->normalizeRoleName($this->attributes['role']);
        }

        return null;
    }

    /**
     * Obtiene el rol del usuario teniendo a Spatie Permission como fuente única de verdad.
     * Si el rol coincide con un caso de UserRole, retorna la instancia del Enum para compatibilidad;
     * si es un rol personalizado en Spatie, retorna el nombre del rol.
     */
    public function getRoleAttribute($value = null): UserRole|string|null
    {
        if ($this->hasPendingSpatieRole) {
            return $this->resolveRoleValue($this->pendingSpatieRole);
        }

        if ($this->exists && static::hasSpatiePermissionTables()) {
            $spatieRoleName = $this->roles->first()?->name;
            if ($spatieRoleName !== null) {
                return $this->resolveRoleValue($spatieRoleName);
            }
        }

        if ($value !== null) {
            return $this->resolveRoleValue($value);
        }

        return null;
    }

    /**
     * Permite asignar el rol mediante $user->role = ... o User::create(['role' => ...])
     * sin requerir la columna física users.role en la base de datos.
     */
    public function setRoleAttribute($value): void
    {
        if (! $this->hasCapturedPreviousRole) {
            $this->previousSpatieRole = $this->exists ? $this->getCurrentSpatieRoleName() : null;
            $this->hasCapturedPreviousRole = true;
        }

        $normalized = $this->normalizeRoleName($value);
        $this->pendingSpatieRole = $normalized;
        $this->hasPendingSpatieRole = true;

        if (Schema::hasColumn($this->getTable(), 'role')) {
            $this->attributes['role'] = $normalized;
        } else {
            unset($this->attributes['role']);
        }
    }

    protected function normalizeRoleName($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $roleName = $value instanceof \BackedEnum ? $value->value : trim((string) $value);
        if ($roleName === '') {
            return null;
        }

        return match ($roleName) {
            'Administrador', 'administrador', 'admin'            => UserRole::ADMINISTRADOR->value,
            'Coordinador territorial', 'coordinador territorial',
            'Coordinador de sector', 'coordinador de sector',
            'Supervisor', 'supervisor'                           => UserRole::COORDINADOR_TERRITORIAL->value,
            'Gestor seccional', 'gestor seccional',
            'Capturista', 'capturista'                           => UserRole::GESTOR_SECCIONAL->value,
            'Presidente de comité', 'presidente de comité'       => UserRole::PRESIDENTE_COMITE->value,
            'Integrante de comité', 'integrante de comité'       => UserRole::INTEGRANTE_COMITE->value,
            'Afines', 'afines', 'Desdoble', 'desdoble'           => UserRole::AFINES->value,
            default                                              => $roleName,
        };
    }

    protected function resolveRoleValue(?string $roleName): UserRole|string|null
    {
        if ($roleName === null || $roleName === '') {
            return null;
        }

        $normalized = $this->normalizeRoleName($roleName);
        if ($normalized === null) {
            return null;
        }

        return UserRole::tryFrom($normalized) ?? $normalized;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'parent_id', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Usuario {$eventName}");
    }

    protected static function booted(): void
    {
        if (! static::$roleAuditPipeRegistered) {
            static::addLogChange(new class implements LoggablePipe
            {
                public function handle(EventLogBag $event, \Closure $next): EventLogBag
                {
                    if ($event->model instanceof User) {
                        $event->changes = $event->model->adjustRoleActivityChanges($event->event, $event->changes);
                    }

                    return $next($event);
                }
            });
            static::$roleAuditPipeRegistered = true;
        }

        static::saving(function (User $user) {
            $user->roleChangeLoggedInCycle = false;
            $user->isCreatingModel = ! $user->exists;

            if (! $user->hasCapturedPreviousRole) {
                $user->previousSpatieRole = $user->exists ? $user->getCurrentSpatieRoleName() : null;
                $user->hasCapturedPreviousRole = true;
            }

            if (array_key_exists('role', $user->attributes) && ! Schema::hasColumn($user->getTable(), 'role')) {
                $user->pendingSpatieRole = $user->normalizeRoleName($user->attributes['role']);
                $user->hasPendingSpatieRole = true;
                unset($user->attributes['role']);
            }
        });

        static::saved(function (User $user) {
            if ($user->hasPendingSpatieRole) {
                $user->syncRoleWithSpatie($user->pendingSpatieRole);
            }

            $user->hasPendingSpatieRole = false;
            $user->pendingSpatieRole = null;
            $user->previousSpatieRole = null;
            $user->hasCapturedPreviousRole = false;
            $user->roleChangeLoggedInCycle = false;
            $user->isCreatingModel = false;
        });
    }

    /**
     * Ajusta los atributos de auditoría de Spatie Activitylog para que el rol
     * se registre como string plano y refleje cambios reales entre el rol anterior y el nuevo.
     */
    public function adjustRoleActivityChanges(string $eventName, array $changes): array
    {
        $newRole = $this->hasPendingSpatieRole
            ? $this->pendingSpatieRole
            : $this->getCurrentSpatieRoleName();

        if ($eventName === 'created') {
            if ($newRole !== null) {
                $changes['attributes']['role'] = $newRole;
            } else {
                unset($changes['attributes']['role']);
            }
            $this->roleChangeLoggedInCycle = true;

            return $changes;
        }

        if ($eventName === 'updated') {
            $oldRole = $this->hasCapturedPreviousRole ? $this->previousSpatieRole : $this->getCurrentSpatieRoleName();

            if ($oldRole !== $newRole) {
                $changes['attributes']['role'] = $newRole;
                $changes['old']['role'] = $oldRole;
                $this->roleChangeLoggedInCycle = true;
            } else {
                unset($changes['attributes']['role'], $changes['old']['role']);
            }

            return $changes;
        }

        if (isset($changes['attributes']['role']) && $changes['attributes']['role'] instanceof \BackedEnum) {
            $changes['attributes']['role'] = $changes['attributes']['role']->value;
        }
        if (isset($changes['old']['role']) && $changes['old']['role'] instanceof \BackedEnum) {
            $changes['old']['role'] = $changes['old']['role']->value;
        }

        return $changes;
    }

    protected function logRoleChangeActivity(?string $oldRole, ?string $newRole): void
    {
        if ($oldRole === $newRole) {
            return;
        }

        $activityTable = config('activitylog.table_name', 'activity_log');
        if (! Schema::hasTable($activityTable)) {
            return;
        }

        $logStatus = app(ActivityLogStatus::class);
        if (! $this->enableLoggingModelsEvents || $logStatus->disabled()) {
            return;
        }

        app(ActivityLogger::class)
            ->useLog($this->getLogNameToUse())
            ->event('updated')
            ->performedOn($this)
            ->withProperties([
                'attributes' => ['role' => $newRole],
                'old'        => ['role' => $oldRole],
            ])
            ->log('Usuario updated');
    }

    public function syncRoleWithSpatie(?string $roleName = null): void
    {
        if (! static::hasSpatiePermissionTables()) {
            return;
        }

        $oldRole = $this->hasCapturedPreviousRole
            ? $this->previousSpatieRole
            : ($this->exists ? $this->getCurrentSpatieRoleName() : null);

        $targetRole = $roleName;

        if ($targetRole === null && $this->hasPendingSpatieRole) {
            $targetRole = $this->pendingSpatieRole;
        }

        if ($targetRole === null && array_key_exists('role', $this->attributes)) {
            $targetRole = $this->attributes['role'];
        }

        $targetRole = $this->normalizeRoleName($targetRole);

        $this->isInternalRoleSync = true;
        try {
            if (empty($targetRole)) {
                if (! $this->isCreatingModel && $this->roles()->exists()) {
                    $this->spatieSyncRoles([]);
                    $this->unsetRelation('roles');

                    if ($this->exists && ! $this->roleChangeLoggedInCycle) {
                        $this->logRoleChangeActivity($oldRole, null);
                    }
                }
                return;
            }

            $role = Role::firstOrCreate([
                'name'       => $targetRole,
                'guard_name' => 'web',
            ]);

            $this->spatieSyncRoles([$role]);
            $this->unsetRelation('roles');

            if ($this->exists && ! $this->isCreatingModel && ! $this->roleChangeLoggedInCycle && $oldRole !== $targetRole) {
                $this->logRoleChangeActivity($oldRole, $targetRole);
            }
        } finally {
            $this->isInternalRoleSync = false;
        }
    }

    public function syncRoles(...$roles)
    {
        $oldRole = (! $this->isInternalRoleSync && $this->exists) ? $this->getCurrentSpatieRoleName() : null;

        $result = $this->spatieSyncRoles(...$roles);
        $this->unsetRelation('roles');

        if (! $this->isInternalRoleSync && $this->exists && ! $this->isCreatingModel && ! $this->roleChangeLoggedInCycle) {
            $newRole = $this->getCurrentSpatieRoleName();
            if ($oldRole !== $newRole) {
                $this->logRoleChangeActivity($oldRole, $newRole);
            }
        }

        return $result;
    }

    public function assignRole(...$roles)
    {
        $oldRole = (! $this->isInternalRoleSync && $this->exists) ? $this->getCurrentSpatieRoleName() : null;

        $result = $this->spatieAssignRole(...$roles);
        $this->unsetRelation('roles');

        if (! $this->isInternalRoleSync && $this->exists && ! $this->isCreatingModel && ! $this->roleChangeLoggedInCycle) {
            $newRole = $this->getCurrentSpatieRoleName();
            if ($oldRole !== $newRole) {
                $this->logRoleChangeActivity($oldRole, $newRole);
            }
        }

        return $result;
    }
}
