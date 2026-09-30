<?php

namespace Tests\Unit\Readers;

use App\Services\Readers\SplCsvRowReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SplCsvRowReaderTest extends TestCase
{
    protected SplCsvRowReader $reader;
    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new SplCsvRowReader();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    protected function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_csv_') . '.csv';
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    public function test_obtiene_encabezados_correctamente_con_coma(): void
    {
        $csv = "seccion,total_lista_nominal,hombres\n101,500,250";
        $filePath = $this->createTempFile($csv);

        $headers = $this->reader->getHeaders($filePath);

        $this->assertEquals(['seccion', 'total_lista_nominal', 'hombres'], $headers);
    }

    public function test_detecta_delimitador_punto_y_coma(): void
    {
        $csv = "seccion;total_lista_nominal;hombres\n101;500;250\n102;600;300";
        $filePath = $this->createTempFile($csv);

        $headers = $this->reader->getHeaders($filePath);
        $this->assertEquals(['seccion', 'total_lista_nominal', 'hombres'], $headers);

        $rows = iterator_to_array($this->reader->readRows($filePath));
        $this->assertCount(2, $rows);
        $this->assertEquals(['101', '500', '250'], $rows[0]);
    }

    public function test_omite_filas_vacias_y_hace_streaming_con_yield(): void
    {
        $csv = "seccion,total\n101,500\n\n\n102,600\n";
        $filePath = $this->createTempFile($csv);

        $rows = iterator_to_array($this->reader->readRows($filePath));

        $this->assertCount(2, $rows);
        $this->assertEquals(['101', '500'], $rows[0]);
        $this->assertEquals(['102', '600'], $rows[1]);
    }

    public function test_lanza_excepcion_si_el_archivo_no_existe(): void
    {
        $this->expectException(RuntimeException::class);
        $this->reader->getHeaders('/ruta/inexistente/archivo.csv');
    }
}
