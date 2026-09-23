<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CasaMesil;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CasaMesilController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $casas = CasaMesil::query()
            ->when($search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('curp', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/CasaMesil/Index', [
            'casas' => $casas,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'curp' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:255',
            'seccion' => 'nullable|string|max:255',
            'manzana' => 'nullable|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        $casa = CasaMesil::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'casa' => $casa]);
        }

        return redirect()->back()->with('success', 'Casa Mesil registrada correctamente.');
    }

    public function update(Request $request, CasaMesil $casas_mesil)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'curp' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:255',
            'seccion' => 'nullable|string|max:255',
            'manzana' => 'nullable|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        $casas_mesil->update($validated);

        return redirect()->back()->with('success', 'Casa Mesil actualizada correctamente.');
    }

    public function destroy(Request $request, CasaMesil $casas_mesil)
    {
        $casas_mesil->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Casa Mesil eliminada correctamente.');
    }
}
