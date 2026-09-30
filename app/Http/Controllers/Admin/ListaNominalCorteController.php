<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListaNominalCorte;
use App\Services\ListaNominalImportService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class ListaNominalCorteController extends Controller
{
    /**
     * Listado histórico de cortes de Lista Nominal.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $cortes = ListaNominalCorte::query()
            ->withCount('detalles')
            ->withSum('detalles', 'total_lista_nominal')
            ->withSum('detalles', 'padron_electoral')
            ->when($search, function ($query, $search) {
                $query->where('fuente', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->latestFirst()
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($cortes);
        }

        return Inertia::render('Admin/ListaNominal/Index', [
            'cortes'  => $cortes,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Registra un nuevo corte oficial (con importación opcional de CSV o XLSX).
     */
    public function store(Request $request, ListaNominalImportService $importService)
    {
        $validated = $request->validate([
            'fecha_corte' => 'required|date',
            'fuente'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'archivo'     => 'nullable|file|mimes:csv,txt,xlsx,xlsm|max:20480',
            'municipio'   => 'nullable|integer',
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);

        DB::beginTransaction();

        try {
            if ($isActive) {
                ListaNominalCorte::where('is_active', true)->update(['is_active' => false]);
            }

            $corte = ListaNominalCorte::create([
                'fecha_corte' => $validated['fecha_corte'],
                'fuente'      => $validated['fuente'],
                'descripcion' => $validated['descripcion'] ?? null,
                'is_active'   => $isActive,
            ]);

            $importResult = null;
            if ($request->hasFile('archivo')) {
                $municipio = (int) ($validated['municipio'] ?? 41);
                $importResult = $this->ejecutarImportacion(
                    $importService,
                    $corte,
                    $request->file('archivo'),
                    $municipio
                );
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'corte'   => $corte,
                    'import'  => $importResult,
                ], 201);
            }

            $msg = 'Corte de Lista Nominal registrado correctamente.';
            if ($importResult) {
                $msg .= " Se importaron {$importResult['total_guardados']} registros.";
            }

            return redirect()->back()->with('success', $msg);
        } catch (Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el corte: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['error' => 'Error al registrar el corte: ' . $e->getMessage()]);
        }
    }

    /**
     * Garantiza la extensión del archivo para que los adaptadores (ej. XLSX) lo reconozcan.
     */
    protected function ejecutarImportacion(
        ListaNominalImportService $importService,
        ListaNominalCorte $corte,
        UploadedFile $uploadedFile,
        int $municipio
    ) : array {
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'csv';
        $tempPath = tempnam(sys_get_temp_dir(), 'web_import_') . '.' . $extension;
        copy($uploadedFile->getRealPath(), $tempPath);

        try{
            return $importService->importFromCsv($corte, $tempPath, $municipio);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Importa masivamente detalles (CSV o XLSX) a un corte existente.
     */
    public function importDetalles(Request $request, ListaNominalCorte $corte, ListaNominalImportService $importService)
    {
        $request->validate([
            'archivo'   => 'required|file|mimes:csv,txt,xlsx,xlsm|max:20480',
            'municipio' => 'nullable|integer',
        ]);

        try {
            $municipio = (int) $request->input('municipio', 41);
            $result = $this->ejecutarImportacion(
                $importService,
                $corte,
                $request->file('archivo'),
                $municipio
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data'    => $result,
                ]);
            }

            return redirect()->back()->with('success', "Importación completada: {$result['total_guardados']} registros guardados.");
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['archivo' => 'Error durante la importación: ' . $e->getMessage()]);
        }
    }

    /**
     * Activa un corte de manera atómica (desactiva todos los demás).
     */
    public function activar(ListaNominalCorte $corte)
    {
        DB::transaction(function () use ($corte) {
            ListaNominalCorte::where('is_active', true)
                ->where('id', '!=', $corte->id)
                ->update(['is_active' => false]);

            $corte->update(['is_active' => true]);
        });

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "El corte con fecha {$corte->fecha_corte->format('Y-m-d')} ha sido activado.",
                'corte'   => $corte->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'El corte ha sido activado correctamente.');
    }

    /**
     * Actualiza los metadatos de un corte.
     */
    public function update(Request $request, ListaNominalCorte $corte)
    {
        $validated = $request->validate([
            'fecha_corte' => 'required|date',
            'fuente'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $corte->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'corte'   => $corte,
            ]);
        }

        return redirect()->back()->with('success', 'Metadatos del corte actualizados correctamente.');
    }

    /**
     * Elimina un corte que no esté activo.
     */
    public function destroy(ListaNominalCorte $corte)
    {
        if ($corte->is_active) {
            $msg = 'No se puede eliminar el corte activo del sistema. Activa otro corte antes de eliminar este.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['corte' => $msg]);
        }

        $corte->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Corte de Lista Nominal eliminado correctamente.',
            ]);
        }

        return redirect()->back()->with('success', 'Corte eliminado correctamente.');
    }
}
