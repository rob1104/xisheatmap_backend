<?php

namespace App\Services;

use App\Enums\SolicitudEstatus;
use App\Enums\UserRole;
use App\Exceptions\ParticipacionAuthorizationException;
use App\Exceptions\ParticipacionValidationException;
use App\Models\SolicitudLogistica;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SolicitudLogisticaService
{
    /**
     * Listado paginado server-side filtrado por alcance territorial (RN-05).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SolicitudLogistica::query()
            ->with([
                'interaccion:id,folio_interno,jornada_id,ciudadano_id,seccion_folio_id,seccion_gps,fecha_servidor',
                'interaccion.ciudadano:id,clave_unica,nombre,apellido_paterno,apellido_materno',
                'seccionElectoral:id,seccion,municipio',
                'tipoApoyo:id,nombre',
                'atendidoPor:id,name',
            ]);

        if (! $user->hasRole(UserRole::ADMINISTRADOR->value)) {
            $seccionIds = $user->assignedSeccionIds();
            if ($seccionIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('seccion_electoral_id', $seccionIds);
            }
        }

        if (! empty($filters['estatus'])) {
            $query->where('estatus', (string) $filters['estatus']);
        }

        if (! empty($filters['seccion_electoral_id'])) {
            $query->where('seccion_electoral_id', (int) $filters['seccion_electoral_id']);
        }

        if (! empty($filters['tipo_apoyo_id'])) {
            $query->where('tipo_apoyo_id', (int) $filters['tipo_apoyo_id']);
        }

        if (! empty($filters['jornada_id'])) {
            $jornadaId = (int) $filters['jornada_id'];
            $query->whereHas('interaccion', fn ($q) => $q->where('jornada_id', $jornadaId));
        }

        return $query
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    /**
     * Actualiza el estatus operativo de una solicitud por su ID inequívoco,
     * validando permiso territorial y transición de estados.
     */
    public function actualizarEstatus(
        User $user,
        SolicitudLogistica $solicitud,
        SolicitudEstatus $nuevoEstatus,
        ?string $descripcionOperativa = null
    ): SolicitudLogistica {
        if (! $user->canAccessSeccion((int) $solicitud->seccion_electoral_id)) {
            throw new ParticipacionAuthorizationException();
        }

        $actual = $solicitud->estatus instanceof SolicitudEstatus
            ? $solicitud->estatus
            : SolicitudEstatus::from((string) $solicitud->estatus);

        if (! $actual->canTransitionTo($nuevoEstatus)) {
            throw new ParticipacionValidationException(
                "Transición de estatus inválida de {$actual->value} a {$nuevoEstatus->value}.",
                ['estatus' => ["No es posible cambiar de {$actual->value} a {$nuevoEstatus->value}."]]
            );
        }

        $solicitud->estatus = $nuevoEstatus;
        $solicitud->atendido_por_id = $user->id;

        if ($descripcionOperativa !== null) {
            $solicitud->descripcion_operativa = trim($descripcionOperativa);
        }

        if ($nuevoEstatus->isTerminal()) {
            $solicitud->fecha_cierre = now();
        }

        $solicitud->save();

        return $solicitud->fresh([
            'interaccion:id,folio_interno,jornada_id,seccion_folio_id,seccion_gps,fecha_servidor',
            'seccionElectoral:id,seccion,municipio',
            'tipoApoyo:id,nombre',
            'atendidoPor:id,name',
        ]);
    }
}
