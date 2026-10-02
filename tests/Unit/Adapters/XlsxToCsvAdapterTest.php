<?php

namespace Tests\Unit\Adapters;

use App\Adapters\XlsxToCsvAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class XlsxToCsvAdapterTest extends TestCase
{
    protected XlsxToCsvAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new XlsxToCsvAdapter();
    }

    public function test_supports_identifica_extensiones_validas(): void
    {
        $this->assertTrue($this->adapter->supports('padron.xlsx'));
        $this->assertTrue($this->adapter->supports('PADRON.XLSX'));
        $this->assertTrue($this->adapter->supports('/var/data/archivo.xlsm'));

        $this->assertFalse($this->adapter->supports('padron.csv'));
        $this->assertFalse($this->adapter->supports('documento.pdf'));
        $this->assertFalse($this->adapter->supports('datos.txt'));
    }

    public function test_lanza_excepcion_si_el_archivo_origen_no_existe(): void
    {
        $this->expectException(RuntimeException::class);
        $this->adapter->convertToCsv('/ruta/inexistente/archivo.xlsx');
    }

    public function test_lanza_excepcion_si_el_archivo_no_es_un_zip_o_xlsx_valido(): void
    {
        $fakePath = tempnam(sys_get_temp_dir(), 'fake_') . '.xlsx';
        file_put_contents($fakePath, 'no soy un zip');

        try {
            $this->expectException(RuntimeException::class);
            $this->adapter->convertToCsv($fakePath);
        } finally {
            @unlink($fakePath);
        }
    }
}
