<?php

namespace Tests\Feature;

use App\Contracts\SpatialServiceInterface;
use App\Facades\Spatial;
use App\Models\Apoyo;
use App\Models\IneRecord;
use App\Models\SeccionElectoral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpatialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Inserta una sección electoral de prueba con un polígono poligonal cerrado en WGS84.
     * Cuadrante aproximado: Longitud -99.16 a -99.12, Latitud 23.70 a 23.76
     */
    protected function createTestSeccion(string $seccion = '9901', int $municipio = 41, int $entidad = 28): SeccionElectoral
    {
        $polyGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [-99.16, 23.70],
                [-99.12, 23.70],
                [-99.12, 23.76],
                [-99.16, 23.76],
                [-99.16, 23.70]
            ]]
        ]);

        DB::table('secciones_electorales')->insert([
            'entidad' => $entidad,
            'municipio' => $municipio,
            'seccion' => $seccion,
            'distrito_federal' => 5,
            'distrito_local' => 14,
            'tipo' => 2,
            'poligono' => DB::raw("ST_GeomFromGeoJSON('{$polyGeoJson}', 1, 4326)"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SeccionElectoral::where('seccion', $seccion)
            ->where('municipio', $municipio)
            ->where('entidad', $entidad)
            ->first();
    }

    /**
     * Test 1: Verificar que el ServiceProvider resuelve la interfaz como Singleton.
     */
    public function test_spatial_service_is_resolved_by_provider(): void
    {
        $service1 = app(SpatialServiceInterface::class);
        $service2 = app('spatial');

        $this->assertInstanceOf(SpatialServiceInterface::class, $service1);
        $this->assertSame($service1, $service2, 'El servicio debe resolverse como Singleton');
    }

    /**
     * Test 2: Soporte y configuración de motor GIS (MySQL 8 vs MariaDB).
     */
    public function test_gis_engine_configuration_and_detection(): void
    {
        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);

        $spatial->setEngine('mariadb');
        $this->assertEquals('mariadb', $spatial->getEngine());
        $this->assertTrue($spatial->isMariaDb());

        $spatial->setEngine('mysql');
        $this->assertEquals('mysql', $spatial->getEngine());
        $this->assertFalse($spatial->isMariaDb());

        $spatial->setEngine('auto');
        $this->assertEquals('auto', $spatial->getEngine());
    }

    /**
     * Test 3: Point-in-Polygon identifica la sección correcta en MySQL 8 (dentro y fuera).
     */
    public function test_point_in_polygon_identifies_seccion_mysql(): void
    {
        $this->createTestSeccion('9901');

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);
        $spatial->setEngine('mysql');

        // Punto dentro del polígono (Plaza de Armas / Centro de Victoria)
        $seccionDentro = $spatial->findSeccionByPoint(23.73, -99.14);
        $this->assertNotNull($seccionDentro);
        $this->assertEquals('9901', $seccionDentro->seccion);
        $this->assertEquals(41, $seccionDentro->municipio);

        // Punto fuera del polígono
        $seccionFuera = $spatial->findSeccionByPoint(23.85, -99.14);
        $this->assertNull($seccionFuera);
    }

    /**
     * Test 4: Asignación de sección a Apoyos vía procesamiento por lotes (Eloquent con chunkById).
     */
    public function test_assign_seccion_to_apoyos_eloquent_batch(): void
    {
        $this->createTestSeccion('9901');

        // Apoyo con coordenadas dentro del polígono
        $apoyoDentro = Apoyo::create([
            'nombre' => 'Juan Perez',
            'telefono' => '8341234567',
            'colonia' => 'Centro',
            'calle_y_numero' => 'Hidalgo 100',
            'latitud' => 23.730000,
            'longitud' => -99.140000,
            'apoyo' => 'Beca',
            'estatus_de_apoyo' => 'Pendiente',
            'seccion' => null,
        ]);

        // Apoyo con coordenadas fuera del polígono
        $apoyoFuera = Apoyo::create([
            'nombre' => 'Maria Lopez',
            'telefono' => '8349876543',
            'colonia' => 'Rural',
            'calle_y_numero' => 'Camino 5',
            'latitud' => 23.950000,
            'longitud' => -99.140000,
            'apoyo' => 'Despensa',
            'estatus_de_apoyo' => 'Pendiente',
            'seccion' => null,
        ]);

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);
        $res = $spatial->assignSeccionToApoyos(false);

        $this->assertEquals('eloquent', $res['modo']);
        $this->assertEquals(2, $res['total_procesados']);
        $this->assertEquals(1, $res['asignados']);
        $this->assertEquals(1, $res['sin_cobertura']);

        $this->assertEquals('9901', $apoyoDentro->fresh()->seccion);
        $this->assertNull($apoyoFuera->fresh()->seccion);
    }

    /**
     * Test 5: Asignación masiva en SQL Bulk (MySQL 8 y MariaDB compatibilidad).
     */
    public function test_assign_seccion_to_apoyos_bulk_sql(): void
    {
        $this->createTestSeccion('9901');

        $apoyo = Apoyo::create([
            'nombre' => 'Pedro Garcia',
            'telefono' => '8341112233',
            'colonia' => 'Centro',
            'calle_y_numero' => 'Juarez 200',
            'latitud' => 23.735000,
            'longitud' => -99.145000,
            'apoyo' => 'Silla de Ruedas',
            'estatus_de_apoyo' => 'Pendiente',
            'seccion' => null,
        ]);

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);
        $spatial->setEngine('mysql');

        $res = $spatial->assignSeccionToApoyos(true);

        $this->assertEquals('sql_bulk', $res['modo']);
        $this->assertGreaterThanOrEqual(1, $res['asignados']);
        $this->assertEquals('9901', $apoyo->fresh()->seccion);
    }

    /**
     * Test 6: Auditoría INE protege datos personales (NO expone claves de elector ni coordenadas).
     */
    public function test_audit_ine_records_protects_pii(): void
    {
        $this->createTestSeccion('9901');

        $admin = User::factory()->create(['role' => 'Administrador']);

        // 1. Registro coincidente (GPS dentro de 9901 y credencial 9901)
        IneRecord::create([
            'user_id' => $admin->id,
            'clave_elector' => 'CONFIDENCIAL000001',
            'nombre' => 'Ciudadano 1',
            'apellido_paterno' => 'Uno',
            'colonia' => 'Centro',
            'seccion' => '9901',
            'latitud' => 23.73,
            'longitud' => -99.14,
        ]);

        // 2. Registro con discrepancia (GPS dentro de 9901 pero credencial dice 8888)
        IneRecord::create([
            'user_id' => $admin->id,
            'clave_elector' => 'CONFIDENCIAL000002',
            'nombre' => 'Ciudadano 2',
            'apellido_paterno' => 'Dos',
            'colonia' => 'Centro',
            'seccion' => '8888',
            'latitud' => 23.73,
            'longitud' => -99.14,
        ]);

        // 3. Registro fuera de polígono
        IneRecord::create([
            'user_id' => $admin->id,
            'clave_elector' => 'CONFIDENCIAL000003',
            'nombre' => 'Ciudadano 3',
            'apellido_paterno' => 'Tres',
            'colonia' => 'Desconocida',
            'seccion' => '9901',
            'latitud' => 24.50,
            'longitud' => -99.14,
        ]);

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);
        $res = $spatial->auditIneRecords(100);

        $this->assertEquals(3, $res['total_analizados']);
        $this->assertEquals(1, $res['coincidentes']);
        $this->assertEquals(1, $res['total_discrepancias']);
        $this->assertEquals(1, $res['total_fuera_cobertura']);

        // Verificación estricta de protección de datos personales (PII)
        foreach ($res['discrepancias'] as $d) {
            $this->assertArrayNotHasKey('clave_elector', $d, 'La clave de elector NO debe exponerse en la auditoría');
            $this->assertArrayNotHasKey('latitud', $d, 'Las coordenadas exactas NO deben exponerse en la auditoría');
            $this->assertArrayNotHasKey('longitud', $d, 'Las coordenadas exactas NO deben exponerse en la auditoría');
            $this->assertArrayHasKey('id', $d);
            $this->assertArrayHasKey('seccion_credencial', $d);
            $this->assertArrayHasKey('seccion_gps_real', $d);
        }

        foreach ($res['fuera_de_poligonos'] as $f) {
            $this->assertArrayNotHasKey('clave_elector', $f, 'La clave de elector NO debe exponerse en la auditoría');
            $this->assertArrayNotHasKey('latitud', $f, 'Las coordenadas exactas NO deben exponerse en la auditoría');
            $this->assertArrayNotHasKey('longitud', $f, 'Las coordenadas exactas NO deben exponerse en la auditoría');
            $this->assertArrayHasKey('id', $f);
            $this->assertArrayHasKey('motivo', $f);
        }
    }

    /**
     * Test 7: Alcance territorial de los conteos en GeoJSON con métricas.
     */
    public function test_territorial_scope_of_counts_in_geojson_metrics(): void
    {
        // Sección en Municipio 41 (Victoria)
        $this->createTestSeccion('9901', 41, 28);
        // Sección en Municipio 99 (Otro Municipio)
        $this->createTestSeccion('8801', 99, 28);

        $admin = User::factory()->create(['role' => 'Administrador']);

        // IneRecords en 9901 (Victoria) y 8801 (Otro)
        IneRecord::create([
            'user_id' => $admin->id,
            'clave_elector' => 'CLAVEVICT000000001',
            'nombre' => 'A',
            'apellido_paterno' => 'B',
            'colonia' => 'Centro',
            'municipio' => 'VICTORIA',
            'seccion' => '9901',
            'latitud' => 23.73,
            'longitud' => -99.14,
        ]);

        IneRecord::create([
            'user_id' => $admin->id,
            'clave_elector' => 'CLAVEOUT0000000001',
            'nombre' => 'C',
            'apellido_paterno' => 'D',
            'colonia' => 'Norte',
            'municipio' => 'OTRO',
            'seccion' => '8801',
            'latitud' => 25.00,
            'longitud' => -99.14,
        ]);

        // Apoyos en 9901 y 8801
        Apoyo::create([
            'nombre' => 'Apoyo Victoria',
            'colonia' => 'Centro',
            'calle_y_numero' => 'Calle 1',
            'latitud' => 23.73,
            'longitud' => -99.14,
            'apoyo' => 'Beca',
            'estatus_de_apoyo' => 'Pendiente',
            'seccion' => '9901',
        ]);

        Apoyo::create([
            'nombre' => 'Apoyo Fuera',
            'colonia' => 'Centro',
            'calle_y_numero' => 'Calle 2',
            'latitud' => 25.00,
            'longitud' => -99.14,
            'apoyo' => 'Despensa',
            'estatus_de_apoyo' => 'Pendiente',
            'seccion' => '8801',
        ]);

        /** @var SpatialServiceInterface $spatial */
        $spatial = app(SpatialServiceInterface::class);

        // Consultar métricas delimitadas al Municipio 41
        $geoJson = $spatial->getSeccionesGeoJsonWithMetrics(41);

        $this->assertEquals('FeatureCollection', $geoJson['type']);
        $this->assertCount(1, $geoJson['features']);

        $feature = $geoJson['features'][0];
        $this->assertEquals('9901', $feature['properties']['seccion']);
        $this->assertEquals(41, $feature['properties']['municipio']);
        $this->assertEquals(1, $feature['properties']['total_simpatizantes']);
        $this->assertEquals(1, $feature['properties']['total_apoyos']);
    }

    /**
     * Test 8: Autenticación obligatoria en rutas /api/spatial/* (invitados reciben 401).
     */
    public function test_spatial_routes_require_authentication(): void
    {
        $this->getJson('/api/spatial/secciones-geojson')->assertStatus(401);

        $this->postJson('/api/spatial/locate-point', [
            'latitud' => 23.73,
            'longitud' => -99.14,
        ])->assertStatus(401);

        $this->getJson('/api/spatial/audit-summary')->assertStatus(401);
    }

    /**
     * Test 9: Autorización en endpoint de auditoría (/api/spatial/audit-summary).
     * No administradores reciben 403; Administrador recibe 200 sin datos sensibles.
     */
    public function test_audit_summary_authorization_and_data_sanitization(): void
    {
        $integrante = User::factory()->create(['role' => 'Integrante de comité']);
        $admin = User::factory()->create(['role' => 'Administrador']);

        // Rol no administrador recibe 403 Forbidden
        $this->actingAs($integrante, 'sanctum')
            ->getJson('/api/spatial/audit-summary')
            ->assertStatus(403);

        // Rol administrador recibe 200 OK
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/spatial/audit-summary')
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_analizados',
                'coincidentes',
                'porcentaje_consistencia',
                'total_discrepancias',
                'total_fuera_cobertura',
                'discrepancias',
                'fuera_de_poligonos',
            ]);

        // Confirmar que en el JSON no viaja ninguna clave de elector ni coordenadas
        $json = $response->getContent();
        $this->assertStringNotContainsString('clave_elector', $json);
        $this->assertStringNotContainsString('latitud', $json);
        $this->assertStringNotContainsString('longitud', $json);
    }

    /**
     * Test 10: Endpoint /api/spatial/locate-point autenticado y con validación.
     */
    public function test_locate_point_authenticated(): void
    {
        $this->createTestSeccion('9901');
        $user = User::factory()->create(['role' => 'Gestor seccional']);

        // Punto dentro
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/spatial/locate-point', [
                'latitud' => 23.73,
                'longitud' => -99.14,
            ])
            ->assertStatus(200)
            ->assertJsonPath('seccion', '9901');

        // Punto fuera de polígono
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/spatial/locate-point', [
                'latitud' => 24.50,
                'longitud' => -99.14,
            ])
            ->assertStatus(404);

        // Validación de campos
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/spatial/locate-point', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitud', 'longitud']);
    }

    /**
     * Test 11: Endpoint /api/spatial/secciones-geojson autenticado.
     */
    public function test_secciones_geojson_authenticated(): void
    {
        $this->createTestSeccion('9901');
        $user = User::factory()->create(['role' => 'Coordinador de sector']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/spatial/secciones-geojson?municipio=41')
            ->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(1, 'features');
    }
}