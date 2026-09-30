<?php

namespace Tests\Feature;

use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use App\Services\ListaNominalImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListaNominalImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_imports_csv_and_resolves_foreign_keys_correctly(): void
    {
        // 1. Crear secciones electorales de prueba
        $seccion1 = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1563',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $seccion2 = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1564',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        // 2. Crear corte de prueba
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-03-31',
            'fuente'      => 'INE - RFE',
            'descripcion' => 'Corte prueba automatizada',
            'is_active'   => true,
        ]);

        // 3. Crear CSV temporal (con secciones '1563', '1564' y una inexistente '9999')
        $csvContent = implode("\n", [
            "seccion,total_lista_nominal,padron_electoral,hombres,mujeres,no_binario",
            "1563,1850,1900,900,950,0",
            "1564,2100,2150,1050,1100,0",
            "9999,500,520,250,270,0", // Sección inexistente en BD
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ln_test_') . '.csv';
        file_put_contents($tmpFile, $csvContent);

        // 4. Ejecutar servicio
        $service = new ListaNominalImportService();
        $result = $service->importFromCsv($corte, $tmpFile, 41);

        // 5. Aserciones
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total_procesados']);
        $this->assertEquals(2, $result['total_guardados']);
        $this->assertEquals(1, $result['total_omitidos']);
        $this->assertContains('9999', $result['secciones_no_encontradas']);

        // Validar base de datos
        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion1->id,
            'total_lista_nominal'    => 1850,
        ]);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion2->id,
            'total_lista_nominal'    => 2100,
        ]);

        // Validar activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ListaNominalCorte::class,
            'subject_id'   => $corte->id,
        ]);

        // Limpieza
        @unlink($tmpFile);
    }

    public function test_imports_xlsx_using_adapter_correctly(): void
    {
        // 1. Crear sección electoral
        $seccion = SeccionElectoral::create([
            'entidad'          => 28,
            'municipio'        => 41,
            'seccion'          => '1565',
            'distrito_federal' => 5,
            'distrito_local'   => 14,
            'tipo'             => 1,
            'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
        ]);

        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-04-30',
            'fuente'      => 'INE - RFE',
            'descripcion' => 'Corte prueba XLSX',
            'is_active'   => true,
        ]);

        // 2. Crear archivo XLSX válido en memoria usando ZipArchive
        $xlsxPath = tempnam(sys_get_temp_dir(), 'ln_xlsx_test_') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($xlsxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<si><t>seccion</t></si>'
            . '<si><t>total_lista_nominal</t></si>'
            . '<si><t>padron_electoral</t></si>'
            . '<si><t>hombres</t></si>'
            . '<si><t>mujeres</t></si>'
            . '<si><t>no_binario</t></si>'
            . '</sst>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>'
            // Fila 1: Encabezados (referencias a sharedStrings 0 a 5)
            . '<row r="1">'
            . '<c r="A1" t="s"><v>0</v></c>'
            . '<c r="B1" t="s"><v>1</v></c>'
            . '<c r="C1" t="s"><v>2</v></c>'
            . '<c r="D1" t="s"><v>3</v></c>'
            . '<c r="E1" t="s"><v>4</v></c>'
            . '<c r="F1" t="s"><v>5</v></c>'
            . '</row>'
            // Fila 2: Datos para sección 1565
            . '<row r="2">'
            . '<c r="A2"><v>1565</v></c>'
            . '<c r="B2"><v>3200</v></c>'
            . '<c r="C2"><v>3300</v></c>'
            . '<c r="D2"><v>1600</v></c>'
            . '<c r="E2"><v>1600</v></c>'
            . '<c r="F2"><v>0</v></c>'
            . '</row>'
            . '</sheetData>'
            . '</worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        // 3. Importar usando ListaNominalImportService
        $service = new ListaNominalImportService();
        $result = $service->importFromCsv($corte, $xlsxPath, 41);

        // 4. Validar resultado
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['total_procesados']);
        $this->assertEquals(1, $result['total_guardados']);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $seccion->id,
            'total_lista_nominal'    => 3200,
        ]);

        @unlink($xlsxPath);
    }
}