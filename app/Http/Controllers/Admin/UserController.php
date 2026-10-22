<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with([
            'roles',
            'parent.roles',
            'children' => fn ($query) => $query->with('roles')->withCount('ines'),
        ])
            ->withCount(['children', 'ines']);

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q
                ->where('roles.name', $request->role)
                ->where('roles.guard_name', 'web'));
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();
        $users->through(fn (User $user) => $this->userPayload($user));

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get()
            ->map(fn (Role $role) => ['value' => $role->name, 'name' => $role->name]);

        $availableParents = User::with('roles')->orderBy('name')->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $this->roleName($user),
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'roles' => $roles,
                'availableParents' => $availableParents,
            ]);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'availableParents' => $availableParents,
            'roles' => $roles,
        ]);
    }

    /** Contrato plano: la jerarquía se calcula desde parent_id, no desde el rol del usuario. */
    public function organigrama()
    {
        $users = User::with('roles')->select('id', 'name', 'parent_id', 'created_at')
            ->withCount('ines')->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $this->roleName($user),
                'parent_id' => $user->parent_id,
                'ines_count' => $user->ines_count,
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('Admin/Users/OrgChart', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'parent_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->validateParent($validated['parent_id'] ?? null);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'parent_id' => $validated['parent_id'] ?? null,
            ]);
            $user->syncRoles([$this->webRole($validated['role'])]);
        });

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'parent_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $this->validateParent($parentId, $user);

        DB::transaction(function () use ($validated, $user, $parentId): void {
            $user->refresh();
            $isAdmin = $user->hasRole(UserRole::ADMINISTRADOR->value);
            $isRemovingAdmin = $isAdmin && $validated['role'] !== UserRole::ADMINISTRADOR->value;

            if ($isRemovingAdmin && $this->adminCountForUpdate() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No puedes revocar el rol del último Administrador del sistema.',
                ]);
            }

            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'parent_id' => $parentId,
            ];
            if (! empty($validated['password'])) {
                $attributes['password'] = Hash::make($validated['password']);
            }

            $user->update($attributes);
            $user->syncRoles([$this->webRole($validated['role'])]);
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'No puedes eliminar tu propia cuenta mientras tienes la sesión activa.']);
        }

        if ($user->ines()->exists()) {
            return back()->withErrors(['message' => 'No se puede eliminar a este usuario porque ya tiene capturas registradas.']);
        }

        if ($user->children()->exists()) {
            return back()->withErrors(['message' => 'No se puede eliminar porque tiene personas a su cargo. Reasígnalas primero.']);
        }

        $deleted = DB::transaction(function () use ($user): bool {
            $user->refresh();
            if ($user->hasRole(UserRole::ADMINISTRADOR->value) && $this->adminCountForUpdate() <= 1) {
                return false;
            }

            $user->delete();
            return true;
        });

        if (! $deleted) {
            return back()->withErrors(['message' => 'No es posible eliminar al último Administrador del sistema.']);
        }

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new UsersExport($request->search, $request->role), 'catalogo_cuentas.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = User::with(['parent.roles', 'roles'])->withCount(['children', 'ines']);
        $this->applyFilters($query, $request);
        $users = $query->orderBy('name')->get();

        return Pdf::loadView('admin.users.pdf', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ])->download('catalogo_cuentas.pdf');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"));
        }
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q
                ->where('roles.name', $request->role)
                ->where('roles.guard_name', 'web'));
        }
    }

    private function validateParent(?int $parentId, ?User $user = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($user && $parentId === $user->id) {
            throw ValidationException::withMessages(['parent_id' => 'Un usuario no puede ser su propio superior.']);
        }

        if (! $user) {
            return;
        }

        $visited = [];
        $currentId = $parentId;
        while ($currentId !== null && ! isset($visited[$currentId])) {
            if ($currentId === $user->id) {
                throw ValidationException::withMessages(['parent_id' => 'La asignación crearía un ciclo en la jerarquía.']);
            }
            $visited[$currentId] = true;
            $currentId = User::whereKey($currentId)->value('parent_id');
        }
    }

    private function webRole(string $name): Role
    {
        return Role::query()->where('guard_name', 'web')->where('name', $name)->firstOrFail();
    }

    private function adminCountForUpdate(): int
    {
        Role::query()
            ->where('guard_name', 'web')
            ->where('name', UserRole::ADMINISTRADOR->value)
            ->lockForUpdate()
            ->first();

        return User::role(UserRole::ADMINISTRADOR->value)->count();
    }

    private function roleName(User $user): ?string
    {
        return $user->roles->first()?->name;
    }

    private function userPayload(User $user): array
    {
        $payload = $user->toArray();
        unset($payload['roles']);
        $payload['role'] = $this->roleName($user);
        $payload['parent'] = $user->parent ? [
            'id' => $user->parent->id,
            'name' => $user->parent->name,
            'role' => $this->roleName($user->parent),
        ] : null;
        $payload['children'] = $user->children->map(fn (User $child) => [
            'id' => $child->id,
            'name' => $child->name,
            'role' => $this->roleName($child),
            'created_at' => $child->created_at,
            'ines_count' => $child->ines_count,
        ])->all();

        return $payload;
    }
}
