<?php

namespace Tests\Feature;

use App\Contracts\SpatialServiceInterface;
use App\Enums\MetodoValidacion;
use App\Enums\SolicitudEstatus;
use App\Enums\UserRole;
use App\Jobs\SendSolicitudLogisticaFcmJob;
use App\Models\Ciudadano;
use App\Models\InteraccionParticipacion;
use App\Models\Jornada;
use App\Models\NotificacionLog;
use App\Models\SeccionElectoral;
use App\Models\SolicitudLogistica;
use App\Models\TipoApoyo;
use App\Models\User;
use App\Models\UserDevice;
use Database\Seeders\ListaNominalPermissionSeeder;
use Database\Seeders\ParticipacionPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParticipacionApiPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([
            ListaNominalPermissionSeeder::class,
            ParticipacionPermissionSeeder::class,
        ]);
        app(SpatialServiceInterface::class)->setEngine('auto');
    }

    private function createSeccion(string $codigo, float $minLng = -99.16, float $minLat = 23.70): SeccionElectoral
    {
        $maxLng = $minLng + 0.04;
        $maxLat = $minLat + 0.06;

        $polyGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [$minLng, $minLat],
                [$maxLng, $minLat],
                [$maxLng, $maxLat],
                [$minLng, $maxLat],
                [$minLng, $minLat],
            ]],
        ]);

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);
        $geomSql = $spatial->isMariaDb()
            ? "ST_GeomFromGeoJSON('{$polyGeoJson}', 1)"
            : "ST_GeomFromGeoJSON('{$polyGeoJson}', 1, 4326)";

        DB::table('secciones_electorales')->insert([
            'entidad' => 28,
            'municipio' => 41,
            'seccion' => $codigo,
            'distrito_federal' => 5,
            'distrito_local' => 14,
            'tipo' => 2,
            'poligono' => DB::raw($geomSql),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SeccionElectoral::where('seccion', $codigo)->firstOrFail();
    }

    private function createJornadaActiva(string $nombre = 'Jornada 2026'): Jornada
    {
        return Jornada::create([
            'nombre' => $nombre,
            'tipo_jornada' => 'PARTICIPACION',
            'fecha_inicio' => now()->subHours(2),
            'fecha_fin' => now()->addHours(8),
            'aviso_privacidad_version' => 'v1.0',
            'activo' => true,
        ]);
    }

    public function test_device_token_registration_and_revocation(): void
    {
        $gestor = User::factory()->create(['role' => UserRole::GESTOR_SECCIONAL->value]);
        Sanctum::actingAs($gestor);

        $this->postJson('/api/v1/devices/token', [
            'dispositivo_id' => 'android-pixel-01',
            'fcm_token' => 'fcm-token-abc-123',
            'plataforma' => 'ANDROID',
        ])->assertOk()
            ->assertJsonPath('data.dispositivo_id', 'android-pixel-01')
            ->assertJsonPath('data.activo', true);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $gestor->id,
            'dispositivo_id' => 'android-pixel-01',
            'fcm_token_hash' => UserDevice::hashToken('fcm-token-abc-123'),
            'activo' => true,
        ]);

        $this->deleteJson('/api/v1/devices/token', [
            'dispositivo_id' => 'android-pixel-01',
        ])->assertOk();

        $device = UserDevice::where('user_id', $gestor->id)->firstOrFail();
        $this->assertFalse($device->activo);
        $this->assertNotNull($device->revoked_at);
    }

    public function test_padron_sync_enforces_user_secciones_and_includes_tombstones(): void
    {
        $sec1 = $this->createSeccion('1563');
        $sec2 = $this->createSeccion('1564', -99.10, 23.70);

        $operador = User::factory()->create(['role' => UserRole::INTEGRANTE_COMITE->value]);
        $operador->seccionesElectorales()->attach($sec1->id);

        $activoSec1 = Ciudadano::create([
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        $bajaSec1 = Ciudadano::create([
            'nombre' => 'Luis',
            'apellido_paterno' => 'Pérez',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 2,
            'activo' => false,
        ]);

        Ciudadano::create([
            'nombre' => 'Otro',
            'apellido_paterno' => 'Sección',
            'seccion_electoral_id' => $sec2->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        Sanctum::actingAs($operador);

        $response = $this->getJson('/api/v1/padron/sync')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.has_more', false);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($activoSec1->id, $ids);
        $this->assertContains($bajaSec1->id, $ids);
    }

    public function test_participacion_idempotency_conflict_gis_folios_and_dedicated_queue(): void
    {
        Queue::fake();

        $sec1 = $this->createSeccion('1563', -99.16, 23.70);
        $jornada1 = $this->createJornadaActiva('Jornada Primaria');
        $jornada2 = $this->createJornadaActiva('Jornada Secundaria');
        $tipoApoyo = TipoApoyo::firstOrFail();

        $operador = User::factory()->create(['role' => UserRole::PRESIDENTE_COMITE->value]);
        $operador->seccionesElectorales()->attach($sec1->id);

        $ciudadano1 = Ciudadano::create([
            'nombre' => 'María',
            'apellido_paterno' => 'Gómez',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        $ciudadano2 = Ciudadano::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ruiz',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        Sanctum::actingAs($operador);

        $uuid1 = (string) Str::uuid();
        $capturaCliente = '2026-11-24T10:15:00Z';

        // 1. Alta inicial con GPS válido + 2 solicitudes logísticas -> 201 + folio 00001 + seccion_gps + Jobs en cola notifications
        $resCreate = $this->withHeader('X-Device-Id', 'device-field-77')
            ->postJson('/api/v1/participacion', [
                'idempotency_uuid' => $uuid1,
                'jornada_id' => $jornada1->id,
                'ciudadano_id' => $ciudadano1->id,
                'metodo_validacion' => MetodoValidacion::QR->value,
                'qr_version' => 1,
                'consentimiento_datos' => true,
                'consentimiento_version' => 'v1.0',
                'fecha_captura_dispositivo' => $capturaCliente,
                'latitud' => 23.73,
                'longitud' => -99.14,
                'solicitudes' => [
                    ['tipo_apoyo_id' => $tipoApoyo->id, 'descripcion_operativa' => 'Requiere silla de ruedas'],
                    ['tipo_apoyo_id' => $tipoApoyo->id, 'descripcion_operativa' => 'Hidratación en domicilio'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.seccion_folio', '1563')
            ->assertJsonPath('data.seccion_gps', '1563')
            ->assertJsonCount(2, 'data.solicitudes');

        $folio1 = $resCreate->json('data.folio_interno');
        $j1Pad = str_pad((string) $jornada1->id, 3, '0', STR_PAD_LEFT);
        $this->assertMatchesRegularExpression("/^HMX-\d{4}-J{$j1Pad}-SEC1563-00001$/", $folio1);
        $this->assertNotEquals($capturaCliente, $resCreate->json('data.fecha_servidor'));

        // Verifica despacho de 2 jobs a la cola dedicada 'notifications'
        Queue::assertPushed(SendSolicitudLogisticaFcmJob::class, 2);
        Queue::assertPushedOn(
            config('participacion.notifications_queue', 'notifications'),
            SendSolicitudLogisticaFcmJob::class
        );

        // Verifica auditoría en activity_log con ip_origen y dispositivo_id
        $activity = Activity::where('subject_type', InteraccionParticipacion::class)
            ->latest('id')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('device-field-77', $activity->properties->get('dispositivo_id'));
        $this->assertNotEmpty($activity->properties->get('ip_origen'));

        // 2. Reintento idempotente con el mismo UUID -> 200 con mismo folio y sin incrementar secuencia
        $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => $uuid1,
            'jornada_id' => $jornada1->id,
            'ciudadano_id' => $ciudadano1->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 1,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.folio_interno', $folio1);

        $this->assertSame(1, (int) DB::table('secuencias_folios')
            ->where('jornada_id', $jornada1->id)
            ->where('seccion_electoral_id', $sec1->id)
            ->value('ultimo_consecutivo'));

        // 3. Conflicto de duplicidad con nuevo UUID en misma jornada -> 409 sin quemar folio y sin PII extra
        $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada1->id,
            'ciudadano_id' => $ciudadano1->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 1,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])
            ->assertStatus(409)
            ->assertJsonPath('folio_existente', $folio1)
            ->assertJsonStructure(['message', 'folio_existente', 'fecha_servidor'])
            ->assertJsonMissingPath('operador');

        $this->assertSame(1, (int) DB::table('secuencias_folios')
            ->where('jornada_id', $jornada1->id)
            ->where('seccion_electoral_id', $sec1->id)
            ->value('ultimo_consecutivo'));

        // 4. Segundo ciudadano SIN GPS -> folio consecutivo 00002 (sin lagunas) y seccion_gps = null
        $res2 = $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada1->id,
            'ciudadano_id' => $ciudadano2->id,
            'metodo_validacion' => MetodoValidacion::MANUAL->value,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])
            ->assertCreated()
            ->assertJsonPath('data.seccion_gps', null);

        $this->assertMatchesRegularExpression("/^HMX-\d{4}-J{$j1Pad}-SEC1563-00002$/", $res2->json('data.folio_interno'));

        // 5. Mismo ciudadano1 en otra jornada -> 201 con consecutivo propio 00001 sin colisión de folio
        $resOtraJornada = $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada2->id,
            'ciudadano_id' => $ciudadano1->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 1,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])->assertCreated();

        $j2Pad = str_pad((string) $jornada2->id, 3, '0', STR_PAD_LEFT);
        $this->assertMatchesRegularExpression("/^HMX-\d{4}-J{$j2Pad}-SEC1563-00001$/", $resOtraJornada->json('data.folio_interno'));
    }

    public function test_rejects_revoked_qr_missing_consent_and_unauthorized_seccion_or_role(): void
    {
        $sec1 = $this->createSeccion('1563');
        $sec2 = $this->createSeccion('1564', -99.10, 23.70);
        $jornada = $this->createJornadaActiva();

        $operador = User::factory()->create(['role' => UserRole::AFINES->value]);
        $operador->seccionesElectorales()->attach($sec1->id);

        $ciudadanoRevocado = Ciudadano::create([
            'nombre' => 'Elena',
            'apellido_paterno' => 'Torres',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 1,
            'activo' => true,
        ]);
        $ciudadanoRevocado->revocarQr(); // qr_version = 2

        $ciudadanoOtraSeccion = Ciudadano::create([
            'nombre' => 'Raúl',
            'apellido_paterno' => 'Díaz',
            'seccion_electoral_id' => $sec2->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        Sanctum::actingAs($operador);

        // QR revocado (envía v=1 cuando vigente es 2) -> 422
        $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada->id,
            'ciudadano_id' => $ciudadanoRevocado->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 1,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['qr_version']);

        // Consentimiento falso -> 422
        $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada->id,
            'ciudadano_id' => $ciudadanoRevocado->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 2,
            'consentimiento_datos' => false,
            'consentimiento_version' => 'v1.0',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['consentimiento_datos']);

        // Sección fuera del alcance territorial del operador -> 403
        $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada->id,
            'ciudadano_id' => $ciudadanoOtraSeccion->id,
            'metodo_validacion' => MetodoValidacion::QR->value,
            'qr_version' => 1,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
        ])->assertForbidden();

        // Rol sin acceso móvil (Administrador) -> 403 en middleware EnsureMobileFieldRole
        $admin = User::factory()->create(['role' => UserRole::ADMINISTRADOR->value]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/jornadas/activa')->assertForbidden();
    }

    public function test_solicitudes_lifecycle_rbac_territorial_and_fcm_job_log(): void
    {
        $sec1 = $this->createSeccion('1563');
        $sec2 = $this->createSeccion('1564', -99.10, 23.70);
        $jornada = $this->createJornadaActiva();
        $tipoApoyo = TipoApoyo::firstOrFail();

        $gestorSec1 = User::factory()->create(['role' => UserRole::GESTOR_SECCIONAL->value]);
        $gestorSec1->seccionesElectorales()->attach($sec1->id);
        UserDevice::create([
            'user_id' => $gestorSec1->id,
            'dispositivo_id' => 'gestor-dev-1',
            'fcm_token' => 'token-gestor-1',
            'fcm_token_hash' => UserDevice::hashToken('token-gestor-1'),
            'plataforma' => 'ANDROID',
            'activo' => true,
            'last_seen_at' => now(),
        ]);

        $gestorSec2 = User::factory()->create(['role' => UserRole::GESTOR_SECCIONAL->value]);
        $gestorSec2->seccionesElectorales()->attach($sec2->id);

        $afines = User::factory()->create(['role' => UserRole::AFINES->value]);
        $afines->seccionesElectorales()->attach($sec1->id);

        $ciudadano = Ciudadano::create([
            'nombre' => 'Sofía',
            'apellido_paterno' => 'Mendoza',
            'seccion_electoral_id' => $sec1->id,
            'qr_version' => 1,
            'activo' => true,
        ]);

        // Crear interacción con solicitud (QUEUE_CONNECTION=sync en phpunit.xml ejecuta el Job)
        Sanctum::actingAs($afines);
        $res = $this->postJson('/api/v1/participacion', [
            'idempotency_uuid' => (string) Str::uuid(),
            'jornada_id' => $jornada->id,
            'ciudadano_id' => $ciudadano->id,
            'metodo_validacion' => MetodoValidacion::MANUAL->value,
            'consentimiento_datos' => true,
            'consentimiento_version' => 'v1.0',
            'solicitudes' => [
                ['tipo_apoyo_id' => $tipoApoyo->id, 'descripcion_operativa' => 'Apoyo traslado'],
            ],
        ])->assertCreated();

        $solicitudId = (int) $res->json('data.solicitudes.0.id');

        // El Job debe haber generado bitácora FCM dirigida al Gestor seccional de sec1
        $this->assertDatabaseHas('notificaciones_log', [
            'referencia_type' => SolicitudLogistica::class,
            'referencia_id' => $solicitudId,
            'destinatario_user_id' => $gestorSec1->id,
            'canal' => 'FCM',
            'estado_envio' => 'PENDIENTE',
        ]);
        $this->assertSame(1, NotificacionLog::count());

        // Afines NO tiene permiso solicitudes.atender -> 403
        $this->getJson('/api/v1/solicitudes')->assertForbidden();
        $this->patchJson("/api/v1/solicitudes/{$solicitudId}/estatus", [
            'estatus' => SolicitudEstatus::EN_PROCESO->value,
        ])->assertForbidden();

        // Gestor de otra sección (sec2) no ve solicitudes de sec1 y recibe 403 al intentar atenderla
        Sanctum::actingAs($gestorSec2);
        $this->getJson('/api/v1/solicitudes')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson("/api/v1/solicitudes/{$solicitudId}/estatus", [
            'estatus' => SolicitudEstatus::EN_PROCESO->value,
        ])->assertForbidden();

        // Gestor de sec1 lista y transiciona PENDIENTE -> EN_PROCESO -> ATENDIDA, y no puede reabrir ATENDIDA -> PENDIENTE
        Sanctum::actingAs($gestorSec1);
        $this->getJson('/api/v1/solicitudes')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/solicitudes/{$solicitudId}/estatus", [
            'estatus' => SolicitudEstatus::EN_PROCESO->value,
        ])->assertOk()
            ->assertJsonPath('data.estatus', SolicitudEstatus::EN_PROCESO->value)
            ->assertJsonPath('data.atendido_por_id', $gestorSec1->id);

        $this->patchJson("/api/v1/solicitudes/{$solicitudId}/estatus", [
            'estatus' => SolicitudEstatus::ATENDIDA->value,
            'descripcion_operativa' => 'Traslado completado sin incidentes',
        ])->assertOk()
            ->assertJsonPath('data.estatus', SolicitudEstatus::ATENDIDA->value);

        $solicitud = SolicitudLogistica::findOrFail($solicitudId);
        $this->assertNotNull($solicitud->fecha_cierre);

        $this->patchJson("/api/v1/solicitudes/{$solicitudId}/estatus", [
            'estatus' => SolicitudEstatus::PENDIENTE->value,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['estatus']);
    }
}
