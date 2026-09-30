<?php

namespace Tests\Unit\Enums;

use App\Enums\ListaNominalPermission;
use PHPUnit\Framework\TestCase;

class ListaNominalPermissionTest extends TestCase
{
    public function test_enum_has_all_expected_cases(): void
    {
        $this->assertEquals('lista-nominal.ver', ListaNominalPermission::VER->value);
        $this->assertEquals('lista-nominal.crear', ListaNominalPermission::CREAR->value);
        $this->assertEquals('lista-nominal.activar', ListaNominalPermission::ACTIVAR->value);
        $this->assertEquals('lista-nominal.editar', ListaNominalPermission::EDITAR->value);
        $this->assertEquals('lista-nominal.eliminar', ListaNominalPermission::ELIMINAR->value);
    }

    public function test_values_returns_all_string_values(): void
    {
        $values = ListaNominalPermission::values();

        $this->assertCount(5, $values);
        $this->assertContains('lista-nominal.ver', $values);
        $this->assertContains('lista-nominal.crear', $values);
        $this->assertContains('lista-nominal.activar', $values);
        $this->assertContains('lista-nominal.editar', $values);
        $this->assertContains('lista-nominal.eliminar', $values);
    }
}
