<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        // Traemos a los usuarios con su jefe (parent), y contamos sus hijos y capturas
        $users = User::with('parent:id,name')
            ->with(['children' => function ($query) {
                $query->select('id', 'parent_id', 'name', 'role')->withCount('ines');
            }])
            ->withCount(['children', 'ines'])
            ->orderBy('role') // Agrupamos por rol (Admin, luego Supervisores, luego Capturistas)
            ->orderBy('name')
            ->get();

        $availableParents = User::whereIn('role', ['Capturista'])
            ->select('id', 'name', 'role')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'availableParents' => $availableParents
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'role'      => ['required', Rule::in(['Administrador', 'Supervisor', 'Capturista'])],
            'parent_id' => 'nullable|exists:users,id',
        ]);

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
            'role'      => ['required', Rule::in(['Administrador', 'Supervisor', 'Capturista'])],
            'parent_id' => 'nullable|exists:users,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'Usuario actualizado correctamente.');
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
