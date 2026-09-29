<?php

namespace Tests\Feature;

use App\Contracts\SpatialServiceInterface;
use App\Models\Apoyo;
use App\Models\IneRecord;
use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BE-3: Integración de SpatialService con seccion_gps y el corte activo de Lista Nominal.
 */
class SpatialListaNominalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(SpatialServiceInterface::class)->setEngine('auto');
        $this->admin = User::factory()->create(['role' => 'Administrador']);
    }

    protected function createTestSeccion(string $seccion, int $municipio = 41, int $entidad = 28): SeccionElectoral
    {
        $polyGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [-99.16, 23.70], [-99.12, 23.70], [-99.12, 23.76], [-99.16, 23.76], [-99.16, 23.70],
            ]],
        ]);

        $geomSql = app(SpatialServiceInterface::class)->isMariaDb()
            ? "ST_GeomFromGeoJSON('{$polyGeoJson}', 1)"
            : "ST_GeomFromGeoJSON('{$polyGeoJson}', 1, 4326)";

        DB::table('secciones_electorales')->insert([
            'entidad' => $entidad,
            'municipio' => $municipio,
            'seccion' => $seccion,
            'distrito_federal' => 5,
            'distrito_local' => 14,
            'tipo' => 2,
            'poligono' => DB::raw($geomSql),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SeccionElectoral::where('seccion', $seccion)->where('municipio', $municipio)->firstOrFail();
    }

    protected function createIne(string $clave, ?string $seccionGps): IneRecord
    {
        return IneRecord::create([
            'user_id' => $this->admin->id,
            'clave_elector' => str_pad($clave, 18, '0'),
            'nombre' => 'Nombre',
            'apellido_paterno' => 'Apellido',
            'colonia' => 'Centro',
            'municipio' => 'VICTORIA',
            'seccion' => $seccionGps ?? '9901',
            'seccion_gps' => $seccionGps,
            'latitud' => 23.73,
            'longitud' => -99.14,
        ]);
    }

    protected function createCorte(bool $activo, string $fecha = '2024-03-31'): ListaNominalCorte
    {
        return ListaNominalCorte::create([
            'fecha_corte' => $fecha,
            'fuente' => 'INE - Dirección Ejecutiva del Registro Federal de Electores',
            'descripcion' => 'Corte de prueba',
            'is_active' => $activo,
        ]);
    }

    protected function feature(array $geoJson, string $seccion): array
    {
        return collect($geoJson['features'])->firstWhere('properties.seccion', $seccion)['properties'];
    }

    public function test_cobertura_se_calcula_con_el_corte_activo_y_seccion_gps(): void
    {
        $s1 = $this->createTestSeccion('9901');
        $s2 = $this->createTestSeccion('9902');

        $viejo = $this->createCorte(false, '2021-03-31');
        $activo = $this->createCorte(true, '2024-03-31');

        ListaNominalDetalle::create(['lista_nominal_corte_id' => $viejo->id, 'seccion_electoral_id' => $s1->id, 'total_lista_nominal' => 50]);
        ListaNominalDetalle::create(['lista_nominal_corte_id' => $activo->id, 'seccion_electoral_id' => $s1->id, 'total_lista_nominal' => 200, 'padron_electoral' => 210]);
        ListaNominalDetalle::create(['lista_nominal_corte_id' => $activo->id, 'seccion_electoral_id' => $s2->id, 'total_lista_nominal' => 100, 'padron_electoral' => 105]);

        // 3 simpatizantes en 9901, 1 en 9902, 2 sin clasificación territorial
        $this->createIne('A1', '9901');
        $this->createIne('A2', '9901');
        $this->createIne('A3', '9901');
        $this->createIne('B1', '9902');
        $this->createIne('C1', null);
        $this->createIne('C2', null);

        $geoJson = app(SpatialServiceInterface::class)->getSeccionesGeoJsonWithMetrics(41);

        $f1 = $this->feature($geoJson, '9901');
        $this->assertSame(200, $f1['total_lista_nominal'], 'Debe usar el corte activo, no el histórico');
        $this->assertSame(210, $f1['padron_electoral']);
        $this->assertSame(3, $f1['total_simpatizantes']);
        $this->assertEquals(1.5, $f1['porcentaje_cobertura']);          // 3 / 200
        $this->assertEquals(75.0, $f1['porcentaje_relativo_municipio']); // 3 / 4

        $f2 = $this->feature($geoJson, '9902');
        $this->assertEquals(1.0, $f2['porcentaje_cobertura']);          // 1 / 100

        $summary = $geoJson['summary'];
        $this->assertSame($activo->id, $summary['corte_activo']['id']);
        $this->assertSame('2024-03-31', $summary['corte_activo']['fecha_corte']);
        $this->assertSame(300, $summary['total_lista_nominal']);
        $this->assertSame(315, $summary['total_padron_electoral']);
        $this->assertSame(4, $summary['simpatizantes']['clasificados_en_secciones']);
        $this->assertSame(2, $summary['simpatizantes']['sin_clasificacion_territorial']);
        $this->assertSame(6, $summary['simpatizantes']['total_general']);
        $this->assertEquals(1.33, $summary['cobertura_global_pct']);     // 4 / 300
    }

    public function test_sin_corte_activo_la_lista_nominal_es_null(): void
    {
        $s1 = $this->createTestSeccion('9901');
        $inactivo = $this->createCorte(false);
        ListaNominalDetalle::create(['lista_nominal_corte_id' => $inactivo->id, 'seccion_electoral_id' => $s1->id, 'total_lista_nominal' => 200]);
        $this->createIne('A1', '9901');

        $geoJson = app(SpatialServiceInterface::class)->getSeccionesGeoJsonWithMetrics(41);
        $f1 = $this->feature($geoJson, '9901');

        $this->assertNull($f1['total_lista_nominal']);
        $this->assertNull($f1['porcentaje_cobertura']);
        $this->assertSame(1, $f1['total_simpatizantes']);
        $this->assertNull($geoJson['summary']['corte_activo']);
        $this->assertNull($geoJson['summary']['total_lista_nominal']);
        $this->assertNull($geoJson['summary']['cobertura_global_pct']);
    }

    public function test_distingue_seccion_sin_dato_de_lista_nominal_en_cero(): void
    {
        $conCero = $this->createTestSeccion('9901');
        $this->createTestSeccion('9902'); // sin detalle en el corte activo
        $activo = $this->createCorte(true);
        ListaNominalDetalle::create(['lista_nominal_corte_id' => $activo->id, 'seccion_electoral_id' => $conCero->id, 'total_lista_nominal' => 0]);
        $this->createIne('A1', '9901');

        $geoJson = app(SpatialServiceInterface::class)->getSeccionesGeoJsonWithMetrics(41);

        $cero = $this->feature($geoJson, '9901');
        $this->assertSame(0, $cero['total_lista_nominal']);
        $this->assertNull($cero['porcentaje_cobertura'], 'Con 0 electores la cobertura no aplica');

        $sinDato = $this->feature($geoJson, '9902');
        $this->assertNull($sinDato['total_lista_nominal']);
        $this->assertNull($sinDato['porcentaje_cobertura']);
    }

    public function test_mantiene_campos_anteriores_y_resumen_de_apoyos(): void
    {
        $this->createTestSeccion('9901');
        $this->createIne('A1', '9901');

        Apoyo::create([
            'nombre' => 'Apoyo', 'colonia' => 'Centro', 'calle_y_numero' => 'Calle 1',
            'latitud' => 23.73, 'longitud' => -99.14, 'apoyo' => 'Beca',
            'estatus_de_apoyo' => 'Pendiente', 'seccion' => '9901',
        ]);
        Apoyo::create([
            'nombre' => 'Apoyo sin sección', 'colonia' => 'Centro', 'calle_y_numero' => 'Calle 2',
            'latitud' => 23.73, 'longitud' => -99.14, 'apoyo' => 'Beca',
            'estatus_de_apoyo' => 'Pendiente', 'seccion' => null,
        ]);

        $geoJson = app(SpatialServiceInterface::class)->getSeccionesGeoJsonWithMetrics(41);
        $f1 = $this->feature($geoJson, '9901');

        // Campos que ya usaba el mapa
        $this->assertEquals(100.0, $f1['porcentaje']);
        $this->assertEquals(100.0, $f1['porcentaje_simpatizantes']);
        $this->assertEquals(100.0, $f1['porcentaje_apoyos']);
        $this->assertSame(1, $geoJson['summary']['total_simpatizantes']);
        $this->assertSame(1, $geoJson['summary']['total_apoyos']);

        $this->assertSame(['clasificados_en_secciones' => 1, 'total_general' => 2], $geoJson['summary']['apoyos']);
        $this->assertSame(41, $geoJson['summary']['municipio']);
    }

    public function test_endpoint_web_devuelve_el_nuevo_contrato(): void
    {
        $s1 = $this->createTestSeccion('9901');
        $activo = $this->createCorte(true);
        ListaNominalDetalle::create(['lista_nominal_corte_id' => $activo->id, 'seccion_electoral_id' => $s1->id, 'total_lista_nominal' => 1850]);

        $this->actingAs($this->admin)
            ->getJson('/spatial/secciones-geojson')
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.properties.total_lista_nominal', 1850)
            ->assertJsonPath('summary.corte_activo.id', $activo->id)
            ->assertJsonStructure([
                'summary' => [
                    'municipio', 'corte_activo', 'total_secciones', 'total_lista_nominal', 'total_padron_electoral',
                    'simpatizantes' => ['clasificados_en_secciones', 'sin_clasificacion_territorial', 'total_general'],
                    'apoyos' => ['clasificados_en_secciones', 'total_general'],
                    'cobertura_global_pct',
                ],
                'features' => [['properties' => [
                    'total_lista_nominal', 'padron_electoral', 'total_simpatizantes', 'total_apoyos',
                    'porcentaje_cobertura', 'porcentaje_relativo_municipio',
                ]]],
            ]);
    }
}
