<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ParticipacionPermission;
use App\Enums\SolicitudEstatus;
use App\Enums\UserRole;
use App\Exceptions\ParticipacionAuthorizationException;
use App\Exceptions\ParticipacionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateSolicitudEstatusRequest;
use App\Models\SolicitudLogistica;
use App\Services\SolicitudLogisticaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudLogisticaController extends Controller
{
    public function __construct(
        private readonly SolicitudLogisticaService $solicitudService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->canAttendSolicitudes($request)) {
            return response()->json([
                'message' => 'No tienes permiso para consultar solicitudes logísticas.',
            ], 403);
        }

        $validated = $request->validate([
            'estatus' => ['nullable', 'string'],
            'seccion_electoral_id' => ['nullable', 'integer'],
            'tipo_apoyo_id' => ['nullable', 'integer'],
            'jornada_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->solicitudService->paginateForUser(
            user: $request->user(),
            filters: $validated,
            perPage: (int) ($validated['per_page'] ?? 20)
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (SolicitudLogistica $s) => [
                'id' => $s->id,
                'folio_interno' => $s->interaccion?->folio_interno,
                'jornada_id' => $s->interaccion?->jornada_id,
                'seccion_electoral_id' => $s->seccion_electoral_id,
                'seccion' => $s->seccionElectoral?->seccion,
                'tipo_apoyo_id' => $s->tipo_apoyo_id,
                'tipo_apoyo' => $s->tipoApoyo?->nombre,
                'descripcion_operativa' => $s->descripcion_operativa,
                'estatus' => $s->estatus->value,
                'atendido_por' => $s->atendidoPor?->name,
                'fecha_cierre' => $s->fecha_cierre?->copy()->utc()->toIso8601String(),
                'created_at' => $s->created_at?->copy()->utc()->toIso8601String(),
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function updateEstatus(UpdateSolicitudEstatusRequest $request, SolicitudLogistica $solicitud): JsonResponse
    {
        if (! $this->canAttendSolicitudes($request)) {
            return response()->json([
                'message' => 'No tienes permiso para atender solicitudes logísticas.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $actualizada = $this->solicitudService->actualizarEstatus(
                user: $request->user(),
                solicitud: $solicitud,
                nuevoEstatus: SolicitudEstatus::from($validated['estatus']),
                descripcionOperativa: $validated['descripcion_operativa'] ?? null
            );
        } catch (ParticipacionAuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ParticipacionValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        }

        return response()->json([
            'message' => 'Estatus de solicitud logística actualizado.',
            'data' => [
                'id' => $actualizada->id,
                'folio_interno' => $actualizada->interaccion?->folio_interno,
                'estatus' => $actualizada->estatus->value,
                'descripcion_operativa' => $actualizada->descripcion_operativa,
                'atendido_por_id' => $actualizada->atendido_por_id,
                'atendido_por' => $actualizada->atendidoPor?->name,
                'fecha_cierre' => $actualizada->fecha_cierre?->copy()->utc()->toIso8601String(),
            ],
        ]);
    }

    private function canAttendSolicitudes(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(UserRole::ADMINISTRADOR->value)
            || $user->hasPermissionTo(ParticipacionPermission::ATENDER_SOLICITUDES->value, 'web');
    }
}
