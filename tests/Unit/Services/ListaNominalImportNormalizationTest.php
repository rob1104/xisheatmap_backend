<?php

namespace Tests\Unit\Services;

use App\Contracts\RowReaderInterface;
use App\Contracts\RowValidatorInterface;
use App\Services\ListaNominalImportService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ListaNominalImportNormalizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected ListaNominalImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ListaNominalImportService(
            Mockery::mock(RowValidatorInterface::class),
            Mockery::mock(RowReaderInterface::class)
        );
    }

    public function test_normaliza_alias_y_acentos_correctamente(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeHeaders');

        $headers = ['Sección', 'LN', 'PADRON', 'Hombres', 'M', 'No_Binario'];
        $normalized = $method->invoke($this->service, $headers);

        $this->assertEquals([
            'seccion',
            'total_lista_nominal',
            'padron_electoral',
            'hombres',
            'mujeres',
            'no_binario',
        ], $normalized);
    }
}
