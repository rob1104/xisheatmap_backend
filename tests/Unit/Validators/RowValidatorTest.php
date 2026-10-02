<?php

namespace Tests\Unit\Validators;

use App\Validators\ListaNominalRowValidator\RowValidator;
use PHPUnit\Framework\TestCase;

class RowValidatorTest extends TestCase
{
    protected RowValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RowValidator();
    }

    public function test_valida_fila_correcta_con_todos_los_campos(): void
    {
        $input = [
            'seccion'             => '1563',
            'total_lista_nominal' => '1850',
            'padron_electoral'    => '1900',
            'hombres'             => '900',
            'mujeres'             => '950',
            'no_binario'          => '0',
        ];

        $result = $this->validator->validate($input);

        $this->assertTrue($result->isValid);
        $this->assertEquals('1563', $result->sanitizedData['seccion']);
        $this->assertEquals(1850, $result->sanitizedData['total_lista_nominal']);
        $this->assertEquals(1900, $result->sanitizedData['padron_electoral']);
        $this->assertEquals(900, $result->sanitizedData['hombres']);
        $this->assertEquals(950, $result->sanitizedData['mujeres']);
        $this->assertEquals(0, $result->sanitizedData['no_binario']);
    }

    public function test_falla_si_la_seccion_es_invalida_o_menor_o_igual_a_cero(): void
    {
        $resultZero = $this->validator->validate(['seccion' => '0', 'total_lista_nominal' => '100']);
        $resultText = $this->validator->validate(['seccion' => 'ABC', 'total_lista_nominal' => '100']);
        $resultNull = $this->validator->validate(['total_lista_nominal' => '100']);

        $this->assertFalse($resultZero->isValid);
        $this->assertFalse($resultText->isValid);
        $this->assertFalse($resultNull->isValid);
    }

    public function test_falla_si_el_total_de_lista_nominal_falta_o_es_negativo(): void
    {
        $resultNull = $this->validator->validate(['seccion' => '100']);
        $resultNegative = $this->validator->validate(['seccion' => '100', 'total_lista_nominal' => '-5']);

        $this->assertFalse($resultNull->isValid);
        $this->assertFalse($resultNegative->isValid);
    }

    public function test_admite_campos_opcionales_vacios_o_nulos(): void
    {
        $input = [
            'seccion'             => '100',
            'total_lista_nominal' => '500',
            'padron_electoral'    => '',
            'hombres'             => null,
            'mujeres'             => '',
        ];

        $result = $this->validator->validate($input);

        $this->assertTrue($result->isValid);
        $this->assertNull($result->sanitizedData['padron_electoral']);
        $this->assertNull($result->sanitizedData['hombres']);
        $this->assertNull($result->sanitizedData['mujeres']);
        $this->assertEquals(0, $result->sanitizedData['no_binario']); // Default a 0
    }

    public function test_falla_con_valores_opcionales_invalidos(): void
    {
        $campos = ['padron_electoral', 'hombres', 'mujeres', 'no_binario'];
        $valoresInvalidos = ['ABC', '-5', '12.5'];

        foreach ($campos as $campo) {
            foreach ($valoresInvalidos as $valor) {
                $input = [
                    'seccion'             => '1563',
                    'total_lista_nominal' => '1850',
                    $campo                => $valor,
                ];

                $result = $this->validator->validate($input);

                $this->assertFalse(
                    $result->isValid,
                    "El campo '{$campo}' con valor '{$valor}' debió fallar la validación."
                );
                $this->assertNotEmpty($result->errorMessage);
            }
        }
    }
}
