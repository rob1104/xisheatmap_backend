<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Admin\ListaNominalCorteController;
use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use App\Services\ListaNominalImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class ListaNominalCorteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ListaNominalCorteController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ListaNominalCorteController;
    }

    public function test_index_returns_paginated_cortes_json(): void
    {
        ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE RFE',
            'descripcion' => 'Corte Inicial',
            'is_active' => true,
        ]);

        $request = Request::create('/admin/lista-nominal/cortes', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('INE RFE', $data[0]['fuente']);
    }

    public function test_store_creates_corte_without_file(): void
    {
        $request = Request::create('/admin/lista-nominal/cortes', 'POST', [
            'fecha_corte' => '2024-02-15',
            'fuente' => 'INE 2024',
            'descripcion' => 'Corte sin archivo',
            'is_active' => '1',
        ]);
        $request->headers->set('Accept', 'application/json');

        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldNotReceive('importFromCsv');

        $response = $this->controller->store($request, $mockService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'fuente' => 'INE 2024',
            'is_active' => false,
        ]);
    }

    public function test_store_creates_corte_and_calls_import_service_when_file_provided(): void
    {
        $file = UploadedFile::fake()->create('corte.csv', 100, 'text/csv');

        $request = Request::create(
            '/admin/lista-nominal/cortes',
            'POST',
            [
                'fecha_corte' => '2024-03-01',
                'fuente' => 'INE CSV',
                'municipio' => '41',
            ],
            [],
            ['archivo' => $file]
        );
        $request->headers->set('Accept', 'application/json');

        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldReceive('importFromCsv')
            ->once()
            ->withArgs(function ($corte, $filePath, $municipio) {
                return $corte instanceof ListaNominalCorte
                    && file_exists($filePath)
                    && $municipio === 41;
            })
            ->andReturn([
                'success' => true,
                'total_guardados' => 150,
            ]);

        $response = $this->controller->store($request, $mockService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(150, $data['import']['total_guardados']);

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'fuente' => 'INE CSV',
        ]);
    }

    public function test_store_rolls_back_transaction_when_import_service_fails(): void
    {
        $file = UploadedFile::fake()->create('corrupted.csv', 100, 'text/csv');

        $request = Request::create(
            '/admin/lista-nominal/cortes',
            'POST',
            [
                'fecha_corte' => '2024-04-01',
                'fuente' => 'INE Error',
            ],
            [],
            ['archivo' => $file]
        );
        $request->headers->set('Accept', 'application/json');

        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldReceive('importFromCsv')
            ->once()
            ->andThrow(new RuntimeException('Formato de columnas inválido'));

        $response = $this->controller->store($request, $mockService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Ocurrió un error al registrar el corte', $data['message']);

        // Verifica que la transacción hizo rollback y no persistió el corte
        $this->assertDatabaseMissing('lista_nominal_cortes', [
            'fuente' => 'INE Error',
        ]);
    }

    public function test_import_detalles_delegates_to_import_service(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Base',
            'is_active' => false,
        ]);

        $file = UploadedFile::fake()->create('detalles.csv', 50, 'text/csv');

        $request = Request::create(
            "/admin/lista-nominal/cortes/{$corte->id}/import",
            'POST',
            ['municipio' => 41],
            [],
            ['archivo' => $file]
        );
        $request->headers->set('Accept', 'application/json');

        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldReceive('importFromCsv')
            ->once()
            ->withArgs(function ($argCorte, $filePath, $municipio) use ($corte) {
                return $argCorte->id === $corte->id
                    && file_exists($filePath)
                    && $municipio === 41;
            })
            ->andReturn([
                'success' => true,
                'total_guardados' => 75,
            ]);

        $response = $this->controller->importDetalles($request, $corte, $mockService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(75, $data['data']['total_guardados']);
    }

    public function test_import_detalles_returns_422_when_import_service_fails(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Base',
            'is_active' => false,
        ]);

        $file = UploadedFile::fake()->create('detalles.csv', 50, 'text/csv');

        $request = Request::create(
            "/admin/lista-nominal/cortes/{$corte->id}/import",
            'POST',
            ['municipio' => 41],
            [],
            ['archivo' => $file]
        );
        $request->headers->set('Accept', 'application/json');

        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldReceive('importFromCsv')
            ->once()
            ->andThrow(new RuntimeException('Error al procesar archivo CSV'));

        $response = $this->controller->importDetalles($request, $corte, $mockService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Ocurrió un error al procesar el archivo', $data['message']);
    }

    public function test_activar_sets_corte_active_and_deactivates_others_atomically(): void
    {
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1563',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $corte1 = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Corte 1',
            'is_active' => true,
        ]);

        $corte2 = ListaNominalCorte::create([
            'fecha_corte' => '2024-02-01',
            'fuente' => 'INE Corte 2',
            'is_active' => false,
        ]);

        ListaNominalDetalle::create([
            'lista_nominal_corte_id' => $corte2->id,
            'seccion_electoral_id'   => $seccion->id,
            'total_lista_nominal'    => 100,
        ]);

        request()->headers->set('Accept', 'application/json');

        $response = $this->controller->activar($corte2);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($response->getData(true)['success']);

        $this->assertFalse($corte1->fresh()->is_active);
        $this->assertTrue($corte2->fresh()->is_active);
    }

    public function test_activar_fails_when_corte_has_no_detalles(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Sin Detalles',
            'is_active' => false,
        ]);

        request()->headers->set('Accept', 'application/json');

        $response = $this->controller->activar($corte);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertFalse($corte->fresh()->is_active);
    }

    public function test_update_modifies_corte_metadata(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Original',
            'descripcion' => 'Original',
            'is_active' => false,
        ]);

        $request = Request::create("/admin/lista-nominal/cortes/{$corte->id}", 'PUT', [
            'fecha_corte' => '2024-06-01',
            'fuente' => 'INE Modificado',
            'descripcion' => 'Actualizado',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = $this->controller->update($request, $corte);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($response->getData(true)['success']);

        $corte->refresh();
        $this->assertEquals('2024-06-01', $corte->fecha_corte->format('Y-m-d'));
        $this->assertEquals('INE Modificado', $corte->fuente);
        $this->assertEquals('Actualizado', $corte->descripcion);
    }

    public function test_destroy_prevents_deleting_active_corte(): void
    {
        $corteActivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Activo',
            'is_active' => true,
        ]);

        request()->headers->set('Accept', 'application/json');

        $response = $this->controller->destroy($corteActivo);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);

        $this->assertDatabaseHas('lista_nominal_cortes', ['id' => $corteActivo->id]);
    }

    public function test_destroy_deletes_inactive_corte(): void
    {
        $corteInactivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Inactivo',
            'is_active' => false,
        ]);

        request()->headers->set('Accept', 'application/json');

        $response = $this->controller->destroy($corteInactivo);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);

        $this->assertDatabaseMissing('lista_nominal_cortes', ['id' => $corteInactivo->id]);
    }

    public function test_ejecutar_importacion_preserves_file_extension_and_cleans_temp_file(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente' => 'INE Test',
            'is_active' => false,
        ]);

        $file = UploadedFile::fake()->create('padron_nominal.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $capturedTempPath = null;
        $mockService = Mockery::mock(ListaNominalImportService::class);
        $mockService->shouldReceive('importFromCsv')
            ->once()
            ->withArgs(function ($argCorte, $filePath, $municipio) use (&$capturedTempPath) {
                $capturedTempPath = $filePath;

                // Verificar que durante la ejecución el archivo temporal existe y tiene la extensión correcta
                return str_ends_with($filePath, '.xlsx') && file_exists($filePath);
            })
            ->andReturn(['total_guardados' => 10]);

        $method = new ReflectionMethod(ListaNominalCorteController::class, 'ejecutarImportacion');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $mockService, $corte, $file, 41);

        $this->assertEquals(['total_guardados' => 10], $result);
        $this->assertNotNull($capturedTempPath);
        // Verificar que el bloque finally eliminó el archivo temporal
        $this->assertFileDoesNotExist($capturedTempPath);
    }
}
