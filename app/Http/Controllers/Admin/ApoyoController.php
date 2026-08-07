<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apoyo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApoyoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $apoyos = Apoyo::query()
            ->when($search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%")
                    ->orWhere('colonia', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Apoyos/Index', [
            'apoyos' => $apoyos,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'colonia' => 'required|string|max:255',
            'calle_y_numero' => 'required|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'apoyo' => 'required|string|max:255',
            'estatus_de_apoyo' => 'required|string|max:255',
        ]);

        Apoyo::create($validated);

        return redirect()->back()->with('success', 'Apoyo registrado correctamente.');
    }

    public function update(Request $request, Apoyo $apoyo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'colonia' => 'required|string|max:255',
            'calle_y_numero' => 'required|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'apoyo' => 'required|string|max:255',
            'estatus_de_apoyo' => 'required|string|max:255',
        ]);

        $apoyo->update($validated);

        return redirect()->back()->with('success', 'Apoyo actualizado correctamente.');
    }

    public function destroy(Apoyo $apoyo)
    {
        $apoyo->delete();

        return redirect()->back()->with('success', 'Apoyo eliminado correctamente.');
    }
}
