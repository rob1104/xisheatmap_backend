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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'parent_id', 'role', 'latitud_actual', 'longitud_actual', 'ultima_conexion_app'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasRoles;

    /**
     * Rol temporal en memoria para sincronizar con Spatie tras guardar el modelo.
     */
    protected ?string $pendingSpatieRole = null;
    protected bool $hasPendingSpatieRole = false;

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
     * Obtiene el rol del usuario teniendo a Spatie Permission como fuente única de verdad.
     * Si el rol coincide con un caso de UserRole, retorna la instancia del Enum para compatibilidad;
     * si es un rol personalizado en Spatie, retorna el nombre del rol.
     */
    public function getRoleAttribute($value = null): UserRole|string|null
    {
        if ($this->hasPendingSpatieRole) {
            return $this->resolveRoleValue($this->pendingSpatieRole);
        }

        if (Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
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

        $roleName = $value instanceof \BackedEnum ? $value->value : (string) $value;

        return match ($roleName) {
            'Administrador', 'administrador', 'admin'           => UserRole::ADMINISTRADOR->value,
            'Coordinador territorial',
            'Coordinador de sector', 'Supervisor', 'supervisor' => UserRole::COORDINADOR_TERRITORIAL->value,
            'Gestor seccional', 'Capturista', 'capturista'      => UserRole::GESTOR_SECCIONAL->value,
            'Presidente de comité'                              => UserRole::PRESIDENTE_COMITE->value,
            'Integrante de comité'                              => UserRole::INTEGRANTE_COMITE->value,
            'Afines', 'Desdoble'                                => UserRole::AFINES->value,
            default                                             => $roleName,
        };
    }

    protected function resolveRoleValue(?string $roleName): UserRole|string|null
    {
        if ($roleName === null || $roleName === '') {
            return null;
        }

        $normalized = $this->normalizeRoleName($roleName);

        return UserRole::tryFrom($normalized) ?? $normalized;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'parent_id', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Usuario {$eventName}");
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (array_key_exists('role', $user->attributes) && ! Schema::hasColumn($user->getTable(), 'role')) {
                $user->pendingSpatieRole = $user->normalizeRoleName($user->attributes['role']);
                $user->hasPendingSpatieRole = true;
                unset($user->attributes['role']);
            }
        });

        static::saved(function (User $user) {
            if ($user->hasPendingSpatieRole) {
                $user->syncRoleWithSpatie($user->pendingSpatieRole);
                $user->hasPendingSpatieRole = false;
                $user->pendingSpatieRole = null;
            }
        });
    }

    public function syncRoleWithSpatie(?string $roleName = null): void
    {
        if (! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
            return;
        }

        $targetRole = $roleName;

        if ($targetRole === null && $this->hasPendingSpatieRole) {
            $targetRole = $this->pendingSpatieRole;
        }

        if ($targetRole === null && array_key_exists('role', $this->attributes)) {
            $targetRole = $this->attributes['role'];
        }

        $targetRole = $this->normalizeRoleName($targetRole);

        if (empty($targetRole)) {
            if (! $this->wasRecentlyCreated && $this->roles()->exists()) {
                $this->syncRoles([]);
                $this->unsetRelation('roles');
            }
            return;
        }

        $role = Role::firstOrCreate([
            'name' => $targetRole,
            'guard_name' => 'web',
        ]);

        $this->syncRoles([$role]);
        $this->unsetRelation('roles');
    }
}
