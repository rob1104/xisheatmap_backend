<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ListaNominalCorte;
use App\Models\SeccionElectoral;
use App\Models\User;
use Database\Seeders\ListaNominalPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use ZipArchive;

class ListaNominalCorteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ListaNominalPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMINISTRADOR,
        ]);
        $this->admin->refresh();
    }

    public function test_can_list_historical_cortes(): void
    {
        ListaNominalCorte::create([
            'fecha_corte' => '2024-01-15',
            'fuente'      => 'INE - RFE',
            'descripcion' => 'Corte Inicial',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.lista-nominal.cortes.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_corte_without_file(): void
    {
        $payload = [
            'fecha_corte' => '2024-03-31',
            'fuente'      => 'INE RFE Oficial',
            'descripcion' => 'Corte previo a elecciones',
            'is_active'   => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.lista-nominal.cortes.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'fuente'    => 'INE RFE Oficial',
            'is_active' => true,
        ]);
    }

    public function test_can_create_corte_with_csv_file(): void
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

        $csvContent = "seccion,total_lista_nominal,padron_electoral,hombres,mujeres,no_binario\n1563,1850,1900,900,950,0";
        $file = UploadedFile::fake()->createWithContent('corte_2024.csv', $csvContent);

        $payload = [
            'fecha_corte' => '2024-03-31',
            'fuente'      => 'INE RFE',
            'descripcion' => 'Corte con archivo directo',
            'is_active'   => false,
            'archivo'     => $file,
            'municipio'   => 41,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.store'), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('import.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal'  => 1850,
        ]);
    }

    public function test_can_create_corte_with_xlsx_file(): void
    {
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1564',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $file = $this->createFakeXlsxFile('corte_excel.xlsx',
            ['seccion', 'total_lista_nominal', 'padron_electoral', 'hombres', 'mujeres', 'no_binario'],
            [['1564', 2500, 2600, 1200, 1300, 0]]
        );

        $payload = [
            'fecha_corte' => '2024-05-15',
            'fuente'      => 'INE Excel Oficial',
            'descripcion' => 'Corte desde Excel',
            'archivo'     => $file,
            'municipio'   => 41,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.store'), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('import.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'seccion_electoral_id' => $seccion->id,
            'total_lista_nominal'  => 2500,
        ]);
    }

    public function test_can_import_detalles_to_existing_corte_with_csv(): void
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

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-04-30',
            'fuente'      => 'INE RFE',
            'is_active'   => false,
        ]);

        $csvContent = "seccion,total_lista_nominal,padron_electoral\n1563,2000,2050";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.import', $corte), [
                'archivo'   => $file,
                'municipio' => 41,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion->id,
            'total_lista_nominal'    => 2000,
        ]);
    }

    public function test_can_import_detalles_to_existing_corte_with_xlsx(): void
    {
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1564',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-05-20',
            'fuente'      => 'INE RFE',
            'is_active'   => false,
        ]);

        $file = $this->createFakeXlsxFile('detalles.xlsx',
            ['seccion', 'total_lista_nominal', 'padron_electoral'],
            [['1564', 2800, 2900]]
        );

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lista-nominal.cortes.import', $corte), [
                'archivo'   => $file,
                'municipio' => 41,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_guardados', 1);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion->id,
            'total_lista_nominal'    => 2800,
        ]);
    }

    public function test_atomic_activation_deactivates_previous_corte(): void
    {
        $corte1 = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente 1',
            'is_active'   => true,
        ]);

        $corte2 = ListaNominalCorte::create([
            'fecha_corte' => '2024-02-01',
            'fuente'      => 'Fuente 2',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('admin.lista-nominal.cortes.activar', $corte2));

        $response->assertStatus(200);

        $this->assertFalse($corte1->fresh()->is_active);
        $this->assertTrue($corte2->fresh()->is_active);
    }

    public function test_can_update_corte_metadata(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Antigua',
            'descripcion' => 'Descripción previa',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.lista-nominal.cortes.update', $corte), [
                'fecha_corte' => '2024-02-01',
                'fuente'      => 'Fuente Actualizada',
                'descripcion' => 'Descripción nueva',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('lista_nominal_cortes', [
            'id'          => $corte->id,
            'fuente'      => 'Fuente Actualizada',
            'descripcion' => 'Descripción nueva',
        ]);
    }

    public function test_cannot_delete_active_corte(): void
    {
        $corteActivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Oficial',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.lista-nominal.cortes.destroy', $corteActivo));

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('lista_nominal_cortes', ['id' => $corteActivo->id]);
    }

    public function test_can_delete_inactive_corte(): void
    {
        $corteInactivo = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Obsoleta',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.lista-nominal.cortes.destroy', $corteInactivo));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('lista_nominal_cortes', ['id' => $corteInactivo->id]);
    }

    public function test_unauthenticated_user_cannot_access_cortes(): void
    {
        $response = $this->getJson(route('admin.lista-nominal.cortes.index'));
        $response->assertStatus(401);
    }

    public function test_unauthorized_role_without_permission_cannot_access_cortes(): void
    {
        $brigadista = User::factory()->create([
            'role' => UserRole::DESDOBLE,
        ]);

        $response = $this->actingAs($brigadista)
            ->getJson(route('admin.lista-nominal.cortes.index'));

        $response->assertStatus(403);
    }

    public function test_gestor_seccional_can_view_cortes_but_cannot_create_activate_or_delete(): void
    {
        $gestor = User::factory()->create([
            'role' => UserRole::GESTOR_SECCIONAL,
        ]);
        $gestor->refresh();

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-01',
            'fuente'      => 'Fuente Oficial',
            'is_active'   => false,
        ]);

        // 1. Ver cortes: Autorizado (200)
        $this->actingAs($gestor)
            ->getJson(route('admin.lista-nominal.cortes.index'))
            ->assertStatus(200);

        // 2. Crear corte: Prohibido (403)
        $this->actingAs($gestor)
            ->postJson(route('admin.lista-nominal.cortes.store'), [
                'fecha_corte' => '2024-06-01',
                'fuente'      => 'INE',
            ])
            ->assertStatus(403);

        // 3. Activar corte: Prohibido (403)
        $this->actingAs($gestor)
            ->patchJson(route('admin.lista-nominal.cortes.activar', $corte))
            ->assertStatus(403);

        // 4. Actualizar metadatos: Prohibido (403)
        $this->actingAs($gestor)
            ->putJson(route('admin.lista-nominal.cortes.update', $corte), [
                'fecha_corte' => '2024-06-01',
                'fuente'      => 'INE Editado',
            ])
            ->assertStatus(403);

        // 5. Eliminar corte: Prohibido (403)
        $this->actingAs($gestor)
            ->deleteJson(route('admin.lista-nominal.cortes.destroy', $corte))
            ->assertStatus(403);
    }

    /**
     * Helper para generar un archivo XLSX en memoria para pruebas HTTP.
     */
    protected function createFakeXlsxFile(string $filename, array $headers, array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'fake_xlsx_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sharedStrings = $headers;
        foreach ($rows as $row) {
            $sharedStrings[] = (string) $row[0];
        }

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        foreach ($sharedStrings as $str) {
            $sharedStringsXml .= '<si><t>' . htmlspecialchars((string) $str) . '</t></si>';
        }
        $sharedStringsXml .= '</sst>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $sheetXml .= '<row r="1">';
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headers as $idx => $header) {
            $sheetXml .= '<c r="' . $cols[$idx] . '1" t="s"><v>' . $idx . '</v></c>';
        }
        $sheetXml .= '</row>';

        $offset = count($headers);
        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $sheetXml .= '<row r="' . $rowNum . '">';
            $sheetXml .= '<c r="A' . $rowNum . '" t="s"><v>' . ($offset + $rIdx) . '</v></c>';
            for ($c = 1; $c < count($row); $c++) {
                $sheetXml .= '<c r="' . $cols[$c] . $rowNum . '"><v>' . (int) $row[$c] . '</v></c>';
            }
            $sheetXml .= '</row>';
        }
        $sheetXml .= '</sheetData></worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
