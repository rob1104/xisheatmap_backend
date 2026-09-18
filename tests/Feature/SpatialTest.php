<?php
namespace Tests\Feature;

use App\Contracts\SpatialServiceInterface;
use App\Facades\Spatial;
use App\Models\SeccionElectoral;
use Tests\TestCase;

class SpatialTest extends TestCase
{
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
     * Test 2: Validar Point-in-Polygon (coordenadas centro de Ciudad Victoria).
     */
    public function test_point_in_polygon_identifies_seccion(): void
    {
        // Coordenadas conocidas en Ciudad Victoria (Plaza de Armas / Palacio de Gobierno)
        $lat = 23.7369;
        $lng = -99.1411;

        $seccion = Spatial::findSeccionByPoint($lat, $lng);

        if (SeccionElectoral::count() > 0) {
            $this->assertNotNull($seccion, 'Debe identificar una sección para el centro de Victoria');
            $this->assertEquals(41, $seccion->municipio);
            $this->assertNotEmpty($seccion->seccion);
        } else {
            $this->markTestSkipped('Tabla secciones_electorales vacía. Ejecuta el seeder primero.');
        }
    }

    /**
     * Test 3: Endpoint GET /api/spatial/secciones-geojson devuelve FeatureCollection válido.
     */
    public function test_geojson_endpoint_returns_valid_features(): void
    {
        $response = $this->getJson('/api/spatial/secciones-geojson');

        $response->assertStatus(200)
                    ->assertJsonStructure([
                        'type',
                        'features' => [
                            '*' => [
                                'type',
                                'properties' => [
                                    'id',
                                    'seccion',
                                    'municipio',
                                    'total_simpatizantes',
                                    'total_apoyos',
                                ],
                                'geometry'
                            ]
                        ]
                    ]);

        $this->assertEquals('FeatureCollection', $response->json('type'));
    }

    /**
     * Test 4: Endpoint POST /api/spatial/locate-point con validación de datos.
     */
    public function test_locate_point_endpoint(): void
    {
        $response = $this->postJson('/api/spatial/locate-point', [
            'latitud' => 23.7369,
            'longitud' => -99.1411,
        ]);

        if (SeccionElectoral::count() > 0) {
            $response->assertStatus(200)
                        ->assertJsonStructure(['id', 'seccion', 'municipio']);
        } else {
            $this->markTestSkipped('Tabla secciones_electorales vacía.');
        }
    }
}