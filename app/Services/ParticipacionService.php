<?php

namespace App\Services;

use App\Contracts\SpatialServiceInterface;
use App\Enums\MetodoValidacion;
use App\Enums\SolicitudEstatus;
use App\Exceptions\ParticipacionAuthorizationException;
use App\Exceptions\ParticipacionConflictException;
use App\Exceptions\ParticipacionValidationException;
use App\Jobs\SendSolicitudLogisticaFcmJob;
use App\Models\Ciudadano;
use App\Models\InteraccionParticipacion;
use App\Models\Jornada;
use App\Models\SolicitudLogistica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ParticipacionService
{
    public function __construct(
        private readonly SpatialServiceInterface $spatialService
    ) {}

    /**
     * Registra una interacción de participación de forma idempotente, transaccional
     * y segura ante concurrencia en secuencias_folios.
     *
     * @param  array<string, mixed>  $data
     * @return array{created: bool, interaccion: InteraccionParticipacion}
     */
    public function registrar(User $operador, array $data): array
    {
        $idempotencyUuid = (string) $data['idempotency_uuid'];

        // 1. Retorno rápido idempotente antes de consumir recursos GIS o bloqueos.
        $existentePorUuid = InteraccionParticipacion::with(['solicitudesLogisticas.tipoApoyo', 'seccionFolio'])
            ->where('idempotency_uuid', $idempotencyUuid)
            ->first();

        if ($existentePorUuid) {
            if (! $operador->canAccessSeccion((int) $existentePorUuid->seccion_folio_id)) {
                throw new ParticipacionAuthorizationException();
            }

            return [
                'created' => false,
                'interaccion' => $existentePorUuid,
            ];
        }

        // 2. Validaciones de negocio y resolución GIS FUERA de la transacción de folios.
        $jornada = Jornada::findOrFail((int) $data['jornada_id']);
        $ahora = now();

        if (! $jornada->isVigenteEn($ahora)) {
            throw new ParticipacionValidationException(
                'La jornada indicada no está activa o se encuentra fuera de vigencia.',
                ['jornada_id' => ['La jornada no está activa o está fuera de su ventana de vigencia.']]
            );
        }

        $ciudadano = Ciudadano::with('seccionElectoral')->findOrFail((int) $data['ciudadano_id']);

        if (! $ciudadano->activo) {
            throw new ParticipacionValidationException(
                'El ciudadano seleccionado no se encuentra activo en el padrón operativo.',
                ['ciudadano_id' => ['El ciudadano no se encuentra activo.']]
            );
        }

        if (! $operador->canAccessSeccion((int) $ciudadano->seccion_electoral_id)) {
            throw new ParticipacionAuthorizationException();
        }

        $metodoValidacion = MetodoValidacion::from((string) $data['metodo_validacion']);
        $qrVersionUsada = isset($data['qr_version']) ? (int) $data['qr_version'] : null;

        if ($metodoValidacion === MetodoValidacion::QR) {
            if ($qrVersionUsada === null || $qrVersionUsada !== (int) $ciudadano->qr_version) {
                throw new ParticipacionValidationException(
                    'El código QR del ciudadano ha sido revocado o su versión es inválida.',
                    ['qr_version' => ['La versión del código QR ha sido revocada o no coincide con la vigente.']]
                );
            }
        }

        $latitud = isset($data['latitud']) && $data['latitud'] !== null ? (float) $data['latitud'] : null;
        $longitud = isset($data['longitud']) && $data['longitud'] !== null ? (float) $data['longitud'] : null;
        $seccionGps = null;

        if ($latitud !== null && $longitud !== null) {
            $seccionEncontrada = $this->spatialService->findSeccionByPoint($latitud, $longitud);
            $seccionGps = $seccionEncontrada?->seccion;
        }

        $fechaCapturaDispositivo = ! empty($data['fecha_captura_dispositivo'])
            ? Carbon::parse((string) $data['fecha_captura_dispositivo'])->setTimezone(config('app.timezone'))
            : null;

        $solicitudesInput = is_array($data['solicitudes'] ?? null) ? $data['solicitudes'] : [];

        // 3. Transacción atómica con control pesimista de secuencias_folios.
        try {
            $resultado = DB::transaction(function () use (
                $idempotencyUuid,
                $jornada,
                $ciudadano,
                $operador,
                $metodoValidacion,
                $qrVersionUsada,
                $data,
                $latitud,
                $longitud,
                $seccionGps,
                $fechaCapturaDispositivo,
                $solicitudesInput
            ): array {
                // Re-verificar idempotencia dentro de la transacción
                $porUuid = InteraccionParticipacion::with(['solicitudesLogisticas.tipoApoyo', 'seccionFolio'])
                    ->where('idempotency_uuid', $idempotencyUuid)
                    ->first();

                if ($porUuid) {
                    return [
                        'created' => false,
                        'interaccion' => $porUuid,
                        'solicitud_ids' => [],
                    ];
                }

                // Verificar unicidad de negocio (ciudadano_id, jornada_id) ANTES de tocar secuencias_folios
                $porTupla = InteraccionParticipacion::query()
                    ->where('ciudadano_id', $ciudadano->id)
                    ->where('jornada_id', $jornada->id)
                    ->first();

                if ($porTupla) {
                    throw new ParticipacionConflictException(
                        folioExistente: $porTupla->folio_interno,
                        fechaServidor: $porTupla->fecha_servidor
                    );
                }

                // RN-07: el folio siempre se numera por ciudadano.seccion_electoral_id (independiente del GPS)
                $seccionFolioId = (int) $ciudadano->seccion_electoral_id;
                $codigoSeccion = (string) ($ciudadano->seccionElectoral?->seccion ?? $seccionFolioId);

                // Asegura existencia de fila inicial sin provocar Duplicate Key ni gap-lock deadlock
                DB::table('secuencias_folios')->insertOrIgnore([
                    'jornada_id' => $jornada->id,
                    'seccion_electoral_id' => $seccionFolioId,
                    'ultimo_consecutivo' => 0,
                ]);

                $secuencia = DB::table('secuencias_folios')
                    ->where('jornada_id', $jornada->id)
                    ->where('seccion_electoral_id', $seccionFolioId)
                    ->lockForUpdate()
                    ->first();

                $siguienteConsecutivo = ((int) ($secuencia?->ultimo_consecutivo ?? 0)) + 1;

                DB::table('secuencias_folios')
                    ->where('jornada_id', $jornada->id)
                    ->where('seccion_electoral_id', $seccionFolioId)
                    ->update(['ultimo_consecutivo' => $siguienteConsecutivo]);

                $fechaServidor = now();
                $folioInterno = $this->formatFolio(
                    $fechaServidor->copy()->utc(),
                    (int) $jornada->id,
                    $codigoSeccion,
                    $siguienteConsecutivo
                );

                $interaccion = InteraccionParticipacion::create([
                    'folio_interno' => $folioInterno,
                    'idempotency_uuid' => $idempotencyUuid,
                    'jornada_id' => $jornada->id,
                    'ciudadano_id' => $ciudadano->id,
                    'user_id' => $operador->id,
                    'seccion_folio_id' => $seccionFolioId,
                    'metodo_validacion' => $metodoValidacion,
                    'qr_version_usada' => $metodoValidacion === MetodoValidacion::QR ? $qrVersionUsada : null,
                    'consentimiento_datos' => true,
                    'consentimiento_version' => (string) $data['consentimiento_version'],
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'seccion_gps' => $seccionGps,
                    'fecha_captura_dispositivo' => $fechaCapturaDispositivo,
                    'fecha_servidor' => $fechaServidor,
                ]);

                $solicitudIds = [];
                foreach ($solicitudesInput as $item) {
                    $solicitud = SolicitudLogistica::create([
                        'interaccion_id' => $interaccion->id,
                        'seccion_electoral_id' => $seccionFolioId,
                        'tipo_apoyo_id' => (int) $item['tipo_apoyo_id'],
                        'descripcion_operativa' => isset($item['descripcion_operativa'])
                            ? trim((string) $item['descripcion_operativa'])
                            : null,
                        'estatus' => SolicitudEstatus::PENDIENTE,
                    ]);
                    $solicitudIds[] = $solicitud->id;
                }

                if ($solicitudIds !== []) {
                    DB::afterCommit(function () use ($solicitudIds): void {
                        foreach ($solicitudIds as $solicitudId) {
                            SendSolicitudLogisticaFcmJob::dispatch($solicitudId);
                        }
                    });
                }

                return [
                    'created' => true,
                    'interaccion' => $interaccion->load(['solicitudesLogisticas.tipoApoyo', 'seccionFolio']),
                    'solicitud_ids' => $solicitudIds,
                ];
            }, 3);

            return [
                'created' => $resultado['created'],
                'interaccion' => $resultado['interaccion'],
            ];
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                return $this->resolveUniqueRaceCondition($idempotencyUuid, $ciudadano->id, $jornada->id);
            }

            throw $e;
        }
    }

    private function formatFolio(Carbon $fechaUtc, int $jornadaId, string $codigoSeccion, int $consecutivo): string
    {
        $prefix = strtoupper((string) config('participacion.folio_prefix', 'HMX'));
        $jornadaPad = str_pad((string) $jornadaId, 3, '0', STR_PAD_LEFT);
        $seccionPad = str_pad($codigoSeccion, 4, '0', STR_PAD_LEFT);
        $seqPad = str_pad((string) $consecutivo, 5, '0', STR_PAD_LEFT);

        return "{$prefix}-{$fechaUtc->format('Y')}-J{$jornadaPad}-SEC{$seccionPad}-{$seqPad}";
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }

    /**
     * @return array{created: bool, interaccion: InteraccionParticipacion}
     */
    private function resolveUniqueRaceCondition(string $idempotencyUuid, int $ciudadanoId, int $jornadaId): array
    {
        $porUuid = InteraccionParticipacion::with(['solicitudesLogisticas.tipoApoyo', 'seccionFolio'])
            ->where('idempotency_uuid', $idempotencyUuid)
            ->first();

        if ($porUuid) {
            return [
                'created' => false,
                'interaccion' => $porUuid,
            ];
        }

        $porTupla = InteraccionParticipacion::query()
            ->where('ciudadano_id', $ciudadanoId)
            ->where('jornada_id', $jornadaId)
            ->first();

        if ($porTupla) {
            throw new ParticipacionConflictException(
                folioExistente: $porTupla->folio_interno,
                fechaServidor: $porTupla->fecha_servidor
            );
        }

        throw new ParticipacionValidationException('Conflicto de integridad al registrar la participación.');
    }
}
