<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // Filtro de Búsqueda (busca en descripción, nombre de usuario o propiedades)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%")
                ->orWhereHas('causer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        // Filtro por tipo de Evento
        if ($request->filled('evento')) {
            $query->where('event', $request->input('evento'));
        }

        $activities = $query->paginate(20)
            ->withQueryString() // Mantiene los filtros al cambiar de página
            ->through(fn ($activity) => [
                'id' => $activity->id,
                'descripcion' => $activity->description,
                'evento' => $activity->event,
                'usuario' => $activity->causer ? $activity->causer->name : 'Sistema',
                'fecha' => $activity->created_at->format('d/m/Y H:i:s'),
                'fecha_relativa' => $activity->created_at->diffForHumans(),
                'propiedades' => $activity->properties,
                'sujeto' => $activity->subject_type ? class_basename($activity->subject_type) : 'N/A'
            ]);

        return Inertia::render('Admin/Logs/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'evento'])
        ]);
    }
}
