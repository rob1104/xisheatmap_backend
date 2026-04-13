<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IneRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class IneRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = IneRecord::with('user:id,name');

        // Filtro por búsqueda (Nombre, Clave o CURP)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('clave_elector', 'like', "%{$request->search}%")
                    ->orWhere('curp', 'like', "%{$request->search}%");
            });
        }

        // Filtro por Brigadista
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        return Inertia::render('Admin/IneRecords/Index', [
            'records' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'user_id']),
            'users' => User::where('role', 'Capturista')->select('id', 'name')->get()
        ]);
    }

    public function showPhoto($id, $tipo)
    {
        $record = IneRecord::findOrFail($id);

        // Determinamos qué columna leer
        $path = $tipo === 'frente' ? $record->foto_frente_path : $record->foto_reverso_path;

        if (!$path) {
            abort(404, 'No hay foto registrada.');
        }

        // Limpiamos la ruta por si acaso trae algún prefijo colado
        $cleanPath = str_replace(['public/', 'private/'], '', $path);

        // --- AHORA SÍ: APUNTAMOS A LA BÓVEDA PRIVADA ---
        $fullPath = storage_path('app/private/' . $cleanPath);

        if (!File::exists($fullPath)) {
            abort(404, 'El archivo físico no se encuentra en la bóveda privada.');
        }

        // Retornamos el archivo directo, forzando los encabezados para imágenes
        return response()->file($fullPath, [
            'Content-Type' => File::mimeType($fullPath)
        ]);
    }
}
