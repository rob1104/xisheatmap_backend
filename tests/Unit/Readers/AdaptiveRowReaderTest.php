<?php

namespace Tests\Unit\Readers;

use App\Contracts\FileToCsvAdapterInterface;
use App\Contracts\RowReaderInterface;
use App\Services\Readers\AdaptiveRowReader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AdaptiveRowReaderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_delega_directamente_si_ningun_adaptador_soporta_el_archivo(): void
    {
        $csvReaderMock = Mockery::mock(RowReaderInterface::class);
        $adapterMock = Mockery::mock(FileToCsvAdapterInterface::class);

        $adapterMock->shouldReceive('supports')->with('datos.csv')->andReturn(false);
        $adapterMock->shouldNotReceive('convertToCsv');

        $csvReaderMock->shouldReceive('getHeaders')
            ->once()
            ->with('datos.csv')
            ->andReturn(['col1', 'col2']);

        $adaptiveReader = new AdaptiveRowReader($csvReaderMock, [$adapterMock]);

        $headers = $adaptiveReader->getHeaders('datos.csv');
        $this->assertEquals(['col1', 'col2'], $headers);
    }

    public function test_convierte_a_csv_temporal_y_lo_elimina_al_finalizar(): void
    {
        $csvReaderMock = Mockery::mock(RowReaderInterface::class);
        $adapterMock = Mockery::mock(FileToCsvAdapterInterface::class);

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_temp_') . '.csv';
        file_put_contents($tempCsv, 'col1,col2');

        $adapterMock->shouldReceive('supports')->with('hoja.xlsx')->andReturn(true);
        $adapterMock->shouldReceive('convertToCsv')->with('hoja.xlsx')->andReturn($tempCsv);

        $csvReaderMock->shouldReceive('getHeaders')
            ->once()
            ->with($tempCsv)
            ->andReturn(['col1', 'col2']);

        $adaptiveReader = new AdaptiveRowReader($csvReaderMock, [$adapterMock]);

        $this->assertFileExists($tempCsv);

        $headers = $adaptiveReader->getHeaders('hoja.xlsx');
        $this->assertEquals(['col1', 'col2'], $headers);

        $this->assertFileDoesNotExist($tempCsv);
    }
}
