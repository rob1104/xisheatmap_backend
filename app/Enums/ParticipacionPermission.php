<?php

namespace App\Enums;

enum ParticipacionPermission: string
{
    case VER = 'participacion.ver';
    case GESTIONAR = 'participacion.gestionar';
    case ATENDER_SOLICITUDES = 'solicitudes.atender';
    case EXPORTAR = 'participacion.exportar';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
