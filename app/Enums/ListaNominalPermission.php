<?php

namespace App\Enums;

enum ListaNominalPermission : string
{
    case VER = 'lista-nominal.ver';
    case CREAR = 'lista-nominal.crear';
    case ACTIVAR = 'lista-nominal.activar';
    case EDITAR = 'lista-nominal.editar';
    case ELIMINAR = 'lista-nominal.eliminar';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
