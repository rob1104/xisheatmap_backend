<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'parent_id', 'role', 'latitud_actual','longitud_actual','ultima_conexion_app'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasRoles;

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
            'role' => \App\Enums\UserRole::class,
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'updated_at']) // Solo nos interesa si cambian estos datos vitales
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Usuario {$eventName}");
    }

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated) {
                if (! empty($user->role)) {
                    $user->syncRoleWithSpatie();
                }  
            } else if ($user->wasChanged('role')) {
                $user->syncRoleWithSpatie();
            }
        });
    }

    public function syncRoleWithSpatie(): void
    {
        if (! Schema::hasTable(config('permission.table_name.roles', 'roles'))) {
            return;
        }

        if (empty($this->role)) {
            if (! $this->wasRecentlyCreated && $this->roles()->exists()) {
                $this->syncRoles([]);
            }
            return;
        }

        $roleName = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;

        if (!$roleName !== ''){
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $this->syncRoles([$role]);
        }
    }
}
