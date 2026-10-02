<?php

namespace Tests\Feature;

use App\Adapters\XlsxToCsvAdapter;
use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use App\Services\ListaNominalImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class ListaNominalRealDataConversionTest extends TestCase
{
    use RefreshDatabase;

    protected array $secciones = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Crear secciones electorales reales del municipio 41 (Victoria, Tamaulipas)
        $seccionesNumeros = ['1560', '1561', '1562', '1563', '1564'];
        foreach ($seccionesNumeros as $sec) {
            $this->secciones[$sec] = SeccionElectoral::create([
                'entidad'          => 28,
                'municipio'        => 41,
                'seccion'          => $sec,
                'distrito_federal' => 5,
                'distrito_local'   => 14,
                'tipo'             => 2, // Urbana
                'poligono'         => DB::raw("ST_GeomFromText('POLYGON((-99.16 23.70, -99.12 23.70, -99.12 23.76, -99.16 23.76, -99.16 23.70))', 4326)"),
            ]);
        }
    }

    /**
     * Prueba importación con formato real del INE en CSV (con BOM UTF-8, acentos y delimitadores variados).
     */
    public function test_conversion_and_import_from_real_ine_csv_format(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-05-31',
            'fuente'      => 'INE - Dirección Ejecutiva del RFE',
            'descripcion' => 'Corte oficial 2024',
            'is_active'   => true,
        ]);

        // Simulación de archivo oficial del INE:
        // - Byte Order Mark (BOM) UTF-8
        // - Encabezados con mayúsculas, espacios y acentos típicos de INE
        // - Columnas adicionales del INE que deben ser ignoradas
        // - Espacios y variaciones en datos
        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom . implode("\n", [
            "CLAVE_ENTIDAD,ENTIDAD,DISTRITO_FED,MUNICIPIO,SECCIÓN,PADRÓN ELECTORAL,LISTA NOMINAL,HOMBRES,MUJERES,NO BINARIOS",
            "28,TAMAULIPAS,05,041,1560,1950,1900,920,980,0",
            "28,TAMAULIPAS,05,041,1561,2150,2100,1030,1070,0",
            "28,TAMAULIPAS,05,041,1562,1420,1400,680,720,0",
            "28,TAMAULIPAS,05,041,1563,,1850,900,950,", // padron y no_binario vacíos
            "28,TAMAULIPAS,05,041,9999,500,480,230,250,0", // Sección fuera de cartografía
            "", // Línea vacía final
        ]);

        $filePath = tempnam(sys_get_temp_dir(), 'ine_csv_real_') . '.csv';
        file_put_contents($filePath, $csvContent);

        $service = app(ListaNominalImportService::class);
        $result = $service->importFromCsv($corte, $filePath, 41);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['total_procesadas']);
        $this->assertEquals(4, $result['insertadas']);
        $this->assertEquals(1, $result['filas_omitidas']); // Sección 9999
        $this->assertContains('9999', $result['secciones_no_encontradas']);

        // Verificar datos en base de datos
        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $this->secciones['1560']->id,
            'total_lista_nominal'    => 1900,
            'padron_electoral'       => 1950,
            'hombres'                => 920,
            'mujeres'                => 980,
            'no_binario'             => 0,
        ]);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $this->secciones['1563']->id,
            'total_lista_nominal'    => 1850,
            'padron_electoral'       => null,
            'hombres'                => 900,
            'mujeres'                => 950,
            'no_binario'             => 0,
        ]);

        @unlink($filePath);
    }

    /**
     * Prueba conversión y procesamiento de un archivo XLSX con estructura real del INE.
     */
    public function test_conversion_and_import_from_real_ine_xlsx_format(): void
    {
        $corte = ListaNominalCorte::create([
            'fecha_corte' => '2024-06-02',
            'fuente'      => 'INE - Jornada Electoral',
            'descripcion' => 'Corte definitivo en Excel',
            'is_active'   => true,
        ]);

        // Crear archivo XLSX con estructura real y celdas dispersas
        $xlsxPath = $this->createRealXlsxFile([
            ['1560', 1880, 1920, 910, 970, 0],
            ['1561', 2050, 2100, 1000, 1050, 0],
            ['1564', 3100, 3150, 1500, 1600, 0],
        ]);

        // 1. Probar el adaptador directamente
        $adapter = new XlsxToCsvAdapter();
        $this->assertTrue($adapter->supports($xlsxPath));

        $convertedCsv = $adapter->convertToCsv($xlsxPath);
        $this->assertFileExists($convertedCsv);
        $csvLines = file($convertedCsv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(4, $csvLines); // 1 header + 3 rows
        $this->assertStringContainsString('seccion', strtolower($csvLines[0]));
        $this->assertStringContainsString('lista_nominal', strtolower($csvLines[0]));
        @unlink($convertedCsv);

        // 2. Probar la importación completa a través del servicio
        $service = app(ListaNominalImportService::class);
        $result = $service->importFromCsv($corte, $xlsxPath, 41);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total_procesados']);
        $this->assertEquals(3, $result['total_guardados']);

        $this->assertDatabaseHas('lista_nominal_detalles', [
            'lista_nominal_corte_id' => $corte->id,
            'seccion_electoral_id'   => $this->secciones['1564']->id,
            'total_lista_nominal'    => 3100,
            'padron_electoral'       => 3150,
            'hombres'                => 1500,
            'mujeres'                => 1600,
            'no_binario'             => 0,
        ]);

        @unlink($xlsxPath);
    }

    /**
     * Prueba de paridad exacta: los mismos datos reales importados desde CSV y desde XLSX
     * deben producir exactamente los mismos registros en base de datos.
     */
    public function test_parity_between_csv_and_xlsx_with_same_real_dataset(): void
    {
        $corteCsv = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-15',
            'fuente'      => 'INE - Dataset CSV',
            'descripcion' => 'Corte CSV para paridad',
            'is_active'   => false,
        ]);

        $corteXlsx = ListaNominalCorte::create([
            'fecha_corte' => '2024-01-15',
            'fuente'      => 'INE - Dataset XLSX',
            'descripcion' => 'Corte XLSX para paridad',
            'is_active'   => false,
        ]);

        $dataset = [
            ['seccion' => '1560', 'ln' => 1890, 'pe' => 1930, 'h' => 915, 'm' => 975, 'nb' => 0],
            ['seccion' => '1561', 'ln' => 2080, 'pe' => 2120, 'h' => 1020, 'm' => 1060, 'nb' => 0],
            ['seccion' => '1562', 'ln' => 1350, 'pe' => 1390, 'h' => 650, 'm' => 700, 'nb' => 0],
            ['seccion' => '1563', 'ln' => 1800, 'pe' => 1850, 'h' => 880, 'm' => 920, 'nb' => 0],
        ];

        // 1. Generar CSV
        $csvLines = ["seccion,total_lista_nominal,padron_electoral,hombres,mujeres,no_binario"];
        foreach ($dataset as $row) {
            $csvLines[] = "{$row['seccion']},{$row['ln']},{$row['pe']},{$row['h']},{$row['m']},{$row['nb']}";
        }
        $csvPath = tempnam(sys_get_temp_dir(), 'parity_') . '.csv';
        file_put_contents($csvPath, implode("\n", $csvLines));

        // 2. Generar XLSX
        $xlsxRows = [];
        foreach ($dataset as $row) {
            $xlsxRows[] = [$row['seccion'], $row['ln'], $row['pe'], $row['h'], $row['m'], $row['nb']];
        }
        $xlsxPath = $this->createRealXlsxFile($xlsxRows);

        // 3. Importar ambos con el servicio
        $service = app(ListaNominalImportService::class);
        $resultCsv = $service->importFromCsv($corteCsv, $csvPath, 41);
        $resultXlsx = $service->importFromCsv($corteXlsx, $xlsxPath, 41);

        // 4. Comparar resultados devueltos
        $this->assertEquals($resultCsv['total_procesados'], $resultXlsx['total_procesados']);
        $this->assertEquals($resultCsv['total_guardados'], $resultXlsx['total_guardados']);
        $this->assertEquals($resultCsv['total_omitidos'], $resultXlsx['total_omitidos']);

        // 5. Comparar registros guardados en base de datos uno a uno
        $detallesCsv = ListaNominalDetalle::where('lista_nominal_corte_id', $corteCsv->id)
            ->orderBy('seccion_electoral_id')
            ->get(['seccion_electoral_id', 'total_lista_nominal', 'padron_electoral', 'hombres', 'mujeres', 'no_binario'])
            ->toArray();

        $detallesXlsx = ListaNominalDetalle::where('lista_nominal_corte_id', $corteXlsx->id)
            ->orderBy('seccion_electoral_id')
            ->get(['seccion_electoral_id', 'total_lista_nominal', 'padron_electoral', 'hombres', 'mujeres', 'no_binario'])
            ->toArray();

        $this->assertNotEmpty($detallesCsv);
        $this->assertEquals($detallesCsv, $detallesXlsx, 'Los datos importados desde CSV y XLSX deben ser idénticos.');

        @unlink($csvPath);
        @unlink($xlsxPath);
    }

    /**
     * Helper para generar un archivo XLSX válido con sharedStrings y sheet1.xml
     */
    protected function createRealXlsxFile(array $rowsData): string
    {
        $path = tempnam(sys_get_temp_dir(), 'real_ine_xlsx_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $headers = ['seccion', 'total_lista_nominal', 'padron_electoral', 'hombres', 'mujeres', 'no_binario'];

        // Construir tabla de cadenas compartidas
        $sharedStrings = $headers;
        foreach ($rowsData as $row) {
            $sharedStrings[] = (string) $row[0]; // sección como string
        }

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        foreach ($sharedStrings as $str) {
            $sharedStringsXml .= '<si><t>' . htmlspecialchars((string) $str) . '</t></si>';
        }
        $sharedStringsXml .= '</sst>';

        // Construir hoja de cálculo
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        // Fila 1: Encabezados
        $sheetXml .= '<row r="1">';
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($cols as $idx => $col) {
            $sheetXml .= '<c r="' . $col . '1" t="s"><v>' . $idx . '</v></c>';
        }
        $sheetXml .= '</row>';

        // Filas de datos
        $sharedIndexOffset = count($headers);
        foreach ($rowsData as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $sheetXml .= '<row r="' . $rowNum . '">';

            // Col A: Sección (string compartido)
            $sheetXml .= '<c r="A' . $rowNum . '" t="s"><v>' . ($sharedIndexOffset + $rIdx) . '</v></c>';

            // Cols B..F: Cifras numéricas directas
            for ($c = 1; $c < count($cols); $c++) {
                $val = $row[$c] ?? '';
                if ($val !== '' && $val !== null) {
                    $sheetXml .= '<c r="' . $cols[$c] . $rowNum . '"><v>' . (int) $val . '</v></c>';
                }
            }

            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $path;
    }
}
