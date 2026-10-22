<?php

namespace App\Enums;

enum RolePermission: string
{
    case VER = 'roles.ver';
    case CREAR = 'roles.crear';
    case EDITAR = 'roles.editar';
    case ELIMINAR = 'roles.eliminar';
    case GESTIONAR = 'roles.gestionar';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}