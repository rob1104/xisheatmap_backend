<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ParticipacionAuthorizationException;
use App\Exceptions\ParticipacionConflictException;
use App\Exceptions\ParticipacionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreParticipacionRequest;
use App\Services\ParticipacionService;
use Illuminate\Http\JsonResponse;

class ParticipacionController extends Controller
{
    public function __construct(
        private readonly ParticipacionService $participacionService
    ) {}

    public function store(StoreParticipacionRequest $request): JsonResponse
    {
        try {
            $resultado = $this->participacionService->registrar(
                operador: $request->user(),
                data: $request->validated()
            );
        } catch (ParticipacionConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'folio_existente' => $e->folioExistente,
                'fecha_servidor' => $e->fechaServidor->copy()->utc()->toIso8601String(),
            ], 409);
        } catch (ParticipacionAuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        } catch (ParticipacionValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        }

        $interaccion = $resultado['interaccion'];
        $status = $resultado['created'] ? 201 : 200;

        return response()->json([
            'message' => $resultado['created']
                ? 'Participación registrada exitosamente.'
                : 'Participación ya sincronizada previamente (idempotente).',
            'data' => [
                'id' => $interaccion->id,
                'folio_interno' => $interaccion->folio_interno,
                'idempotency_uuid' => $interaccion->idempotency_uuid,
                'jornada_id' => $interaccion->jornada_id,
                'ciudadano_id' => $interaccion->ciudadano_id,
                'seccion_folio_id' => $interaccion->seccion_folio_id,
                'seccion_folio' => $interaccion->seccionFolio?->seccion,
                'seccion_gps' => $interaccion->seccion_gps,
                'metodo_validacion' => $interaccion->metodo_validacion->value,
                'qr_version_usada' => $interaccion->qr_version_usada,
                'consentimiento_datos' => $interaccion->consentimiento_datos,
                'consentimiento_version' => $interaccion->consentimiento_version,
                'fecha_captura_dispositivo' => $interaccion->fecha_captura_dispositivo?->copy()->utc()->toIso8601String(),
                'fecha_servidor' => $interaccion->fecha_servidor->copy()->utc()->toIso8601String(),
                'solicitudes' => $interaccion->solicitudesLogisticas->map(fn ($s) => [
                    'id' => $s->id,
                    'tipo_apoyo_id' => $s->tipo_apoyo_id,
                    'tipo_apoyo' => $s->tipoApoyo?->nombre,
                    'descripcion_operativa' => $s->descripcion_operativa,
                    'estatus' => $s->estatus->value,
                ])->values()->all(),
            ],
        ], $status);
    }
}
