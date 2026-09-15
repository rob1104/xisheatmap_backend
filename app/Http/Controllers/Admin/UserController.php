<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('parent:id,name,role')
            ->with(['children' => function ($q) {
                $q->select('id', 'parent_id', 'name', 'role')->withCount('ines');
            }])
            ->withCount(['children', 'ines']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('role')->orderBy('name')->paginate(10)->withQueryString();

        // En lugar de enviar un availableParents estático, mandamos todos y dejamos que Vue los filtre, 
        // o mandamos un arreglo con todos los posibles parents con su ID, nombre y rol.
        $availableParents = User::select('id', 'name', 'role')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'availableParents' => $availableParents,
            'roles' => collect(UserRole::cases())->map(fn($role) => [
                'value' => $role->value,
                'name' => $role->value,
                'level' => $role->level(),
                'parentLevel' => $role->parentLevel()
            ])->toArray()
        ]);
    }

    public function organigrama()
    {
        // Traemos todos los usuarios (excepto el mismo si se quiere, o todos)
        // Para el organigrama necesitamos un árbol.
        // Forma fácil: obtener todos y armar el árbol en Vue, o armarlo aquí.
        // Vamos a enviar la lista plana con parent_id y que una librería o lógica en Vue la renderice.
        $users = User::select('id', 'name', 'role', 'parent_id')
            ->withCount('ines')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role->value,
                    'level' => $user->role->level(),
                    'parent_id' => $user->parent_id,
                    'ines_count' => $user->ines_count,
                ];
            });

        return Inertia::render('Admin/Users/OrgChart', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'role'      => ['required', new Enum(UserRole::class)],
            'parent_id' => 'nullable|exists:users,id',
        ]);

        $this->validateHierarchy($validated['role'], $validated['parent_id']);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'  => 'nullable|string|min:8',
            'role'      => ['required', new Enum(UserRole::class)],
            'parent_id' => 'nullable|exists:users,id',
        ]);

        $this->validateHierarchy($validated['role'], $validated['parent_id']);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    private function validateHierarchy(string $roleValue, ?int $parentId): void
    {
        $role = UserRole::from($roleValue);
        $expectedParentLevel = $role->parentLevel();

        if ($expectedParentLevel === null) {
            if ($parentId !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'parent_id' => "El rol {$role->value} no debe tener un superior asignado."
                ]);
            }
            return;
        }

        if ($parentId === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => "El rol {$role->value} requiere tener un superior asignado."
            ]);
        }

        $parent = User::find($parentId);
        if (!$parent) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => "El superior seleccionado no existe."
            ]);
        }

        if ($parent->role->level() !== $expectedParentLevel) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => "El superior de un {$role->value} debe ser de nivel inmediato superior."
            ]);
        }
    }

    public function destroy(User $user)
    {
        // 1. REGLA DE NEGOCIO: No puedes borrarte a ti mismo
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Acción denegada: No puedes eliminar tu propia cuenta mientras tienes la sesión activa.']);
        }

        // 2. REGLA DE NEGOCIO: No borrar si ya capturó INEs
        if ($user->ines()->count() > 0) {
            return back()->withErrors(['message' => 'No se puede eliminar a este usuario porque ya tiene capturas registradas.']);
        }

        // 3. REGLA DE NEGOCIO: No borrar si tiene gente a su cargo
        if ($user->children()->count() > 0) {
            return back()->withErrors(['message' => 'No se puede eliminar porque tiene brigadistas a su cargo. Reasígnalos primero.']);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
