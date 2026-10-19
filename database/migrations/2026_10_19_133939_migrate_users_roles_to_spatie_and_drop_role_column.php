<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected array $canonicalRoles = [
        'Administrador',
        'Coordinador territorial',
        'Gestor seccional',
        'Presidente de comité',
        'Integrante de comité',
        'Afines',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Asegurar roles canónicos en Spatie
        $roleMap = [];
        foreach ($this->canonicalRoles as $name) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $roleMap[$name] = $role->id;
        }

        // 2. Normalizar nombres históricos y migrar a Spatie garantizando rol único
        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->select('id', 'role')->get();

            foreach ($users as $user) {
                $targetRole = match ($user->role) {
                    'Administrador', 'administrador', 'admin'           => 'Administrador',
                    'Coordinador territorial',
                    'Coordinador de sector', 'Supervisor', 'supervisor' => 'Coordinador territorial',
                    'Gestor seccional', 'Capturista', 'capturista'      => 'Gestor seccional',
                    'Presidente de comité'                              => 'Presidente de comité',
                    'Integrante de comité'                              => 'Integrante de comité',
                    'Afines', 'Desdoble'                                => 'Afines',
                    default                                             => 'Gestor seccional',
                };

                $targetRoleId = $roleMap[$targetRole];

                // Limpiar cualquier relación residual previa en model_has_roles para este usuario
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->delete();

                // Insertar exactamente un único rol en Spatie
                DB::table('model_has_roles')->insert([
                    'role_id'    => $targetRoleId,
                    'model_type' => User::class,
                    'model_id'   => $user->id,
                ]);
            }

            // 3. Validación de integridad: Cero huérfanos
            $totalUsers = DB::table('users')->count();
            $totalAssigned = DB::table('model_has_roles')->where('model_type', User::class)->count();

            if ($totalUsers !== $totalAssigned) {
                throw new \RuntimeException("Falla de integridad: {$totalUsers} usuarios en total pero {$totalAssigned} asignados en Spatie.");
            }

            // 4. Eliminación definitiva de la columna redundante users.role
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            // Limpiar roles con nomenclatura antigua si quedaron sin asignaciones
            DB::table('roles')
                ->whereIn('name', ['Coordinador de sector', 'Desdoble'])
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->whereColumn('model_has_roles.role_id', 'roles.id');
                })
                ->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Recrear columna users.role si no existe
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('Gestor seccional')->after('email');
            });
        }

        // 2. Restaurar datos desde Spatie remapeando a la nomenclatura original
        $assignments = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->select('model_has_roles.model_id', 'roles.name')
            ->get();

        foreach ($assignments as $row) {
            $originalName = match ($row->name) {
                'Coordinador territorial' => 'Coordinador de sector',
                'Afines'                  => 'Desdoble',
                default                   => $row->name,
            };

            DB::table('users')->where('id', $row->model_id)->update(['role' => $originalName]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
