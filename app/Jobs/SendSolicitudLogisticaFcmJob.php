<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\NotificacionLog;
use App\Models\PlantillaMensaje;
use App\Models\SolicitudLogistica;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job asíncrono en cola dedicada 'notifications' para despacho de alertas FCM
 * al coordinador/gestor asignado a la sección electoral de la solicitud logística.
 *
 * En Fase 1 persiste la trazabilidad en notificaciones_log y prepara el payload
 * desde plantillas_mensajes autorizadas; en Fase 4 se conecta el cliente FCM HTTP v1.
 */
class SendSolicitudLogisticaFcmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const PLANTILLA_CODIGO = 'SOLICITUD_LOGISTICA_NUEVA';

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 45, 120];

    public function __construct(
        public readonly int $solicitudLogisticaId
    ) {
        $this->onQueue((string) config('participacion.notifications_queue', 'notifications'));
    }

    public function handle(): void
    {
        $solicitud = SolicitudLogistica::with([
            'interaccion',
            'seccionElectoral',
            'tipoApoyo',
        ])->find($this->solicitudLogisticaId);

        if (! $solicitud) {
            return;
        }

        $plantilla = PlantillaMensaje::query()
            ->active()
            ->where('codigo', self::PLANTILLA_CODIGO)
            ->where('canal', 'FCM')
            ->first();

        if (! $plantilla) {
            Log::warning('No existe plantilla FCM activa para solicitudes logísticas.', [
                'codigo' => self::PLANTILLA_CODIGO,
                'solicitud_id' => $solicitud->id,
            ]);

            return;
        }

        $destinatarios = User::query()
            ->whereHas('seccionesElectorales', fn ($q) => $q->where('secciones_electorales.id', $solicitud->seccion_electoral_id))
            ->whereHas('roles', fn ($q) => $q
                ->where('guard_name', 'web')
                ->whereIn('name', [
                    UserRole::GESTOR_SECCIONAL->value,
                    UserRole::COORDINADOR_TERRITORIAL->value,
                ]))
            ->with(['devices' => fn ($q) => $q->active()])
            ->get();

        $mensaje = $plantilla->render([
            'folio' => $solicitud->interaccion?->folio_interno,
            'seccion' => $solicitud->seccionElectoral?->seccion,
            'tipo_apoyo' => $solicitud->tipoApoyo?->nombre,
        ]);

        foreach ($destinatarios as $destinatario) {
            $tokens = $destinatario->devices
                ->map(fn (UserDevice $device) => $device->fcm_token)
                ->filter()
                ->values()
                ->all();

            NotificacionLog::create([
                'referencia_type' => SolicitudLogistica::class,
                'referencia_id' => $solicitud->id,
                'destinatario_user_id' => $destinatario->id,
                'tipo_destinatario' => 'OPERADOR',
                'canal' => 'FCM',
                'mensaje_plantilla_id' => $plantilla->id,
                'estado_envio' => count($tokens) > 0 ? 'PENDIENTE' : 'FALLIDO',
                'intentos' => $this->attempts(),
                'provider_message_id' => null,
                'error_detalle' => count($tokens) > 0
                    ? null
                    : 'El destinatario no cuenta con dispositivos FCM activos registrados.',
                'fecha_envio' => null,
            ]);

            Log::info('Notificación FCM preparada para solicitud logística.', [
                'solicitud_id' => $solicitud->id,
                'destinatario_user_id' => $destinatario->id,
                'tokens_count' => count($tokens),
                'mensaje' => $mensaje,
            ]);
        }
    }
}
