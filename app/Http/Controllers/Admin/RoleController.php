<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Devuelve la lista de los 6 roles base protegidos del sistema.
     *
     * @return array<string>
     */
    protected function protectedRoles(): array
    {
        return UserRole::values();
    }

    /**
     * Listado de roles de sistema, conteo de usuarios y permisos asociados.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $protected = $this->protectedRoles();

        $roles = Role::where('guard_name', 'web')
            ->with('permissions:id,name')
            ->withCount('users')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->get()
            ->map(function (Role $role) use ($protected) {
                return [
                    'id'           => $role->id,
                    'name'         => $role->name,
                    'guard_name'   => $role->guard_name,
                    'users_count'  => $role->users_count,
                    'is_protected' => in_array($role->name, $protected, true),
                    'permissions'  => $role->permissions->pluck('name')->values()->all(),
                    'created_at'   => $role->created_at,
                ];
            });

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->wantsJson()) {
            return response()->json([
                'roles'       => $roles,
                'permissions' => $permissions,
            ]);
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles'          => $roles,
            'permissions'    => $permissions,
            'protectedRoles' => $protected,
            'filters'        => $request->only('search'),
        ]);
    }

    /**
     * Crea un nuevo rol personalizado con guard_name fijo en 'web'.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::create([
            'name'       => trim($validated['name']),
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Rol creado exitosamente.');
    }

    /**
     * Actualiza un rol existente.
     * - Si es un rol protegido, no permite cambiar su nombre, pero sí sincronizar permisos.
     * - guard_name permanece fijo en 'web'.
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        if ($role->guard_name !== 'web') {
            abort(403, 'Solo se permite gestionar roles del guard web.');
        }

        $isProtected = in_array($role->name, $this->protectedRoles(), true);

        // Si es rol protegido y envían un nombre distinto al original, rechazar explícitamente
        if ($isProtected && $request->filled('name') && trim((string) $request->input('name')) !== $role->name) {
            return back()->withErrors([
                'name' => "El nombre del rol base '{$role->name}' está protegido y no puede modificarse.",
            ]);
        }

        $rules = [
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];

        if (! $isProtected) {
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role->id),
            ];
        }

        $validated = $request->validate($rules);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if (! $isProtected && isset($validated['name'])) {
            $role->update([
                'name' => trim($validated['name']),
            ]);
        }

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    /**
     * Elimina un rol personalizado si no es protegido y no tiene usuarios asignados.
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if ($role->guard_name !== 'web') {
            abort(403, 'Solo se permite gestionar roles del guard web.');
        }

        if (in_array($role->name, $this->protectedRoles(), true)) {
            return back()->withErrors([
                'message' => "Acción denegada: El rol base '{$role->name}' está protegido por el sistema y no puede eliminarse.",
            ]);
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return back()->withErrors([
                'message' => "No es posible eliminar el rol '{$role->name}' porque tiene {$usersCount} usuario(s) asignado(s). Reasígnalos primero.",
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}