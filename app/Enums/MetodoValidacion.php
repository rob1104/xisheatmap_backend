<?php

namespace App\Enums;

enum MetodoValidacion: string
{
    case QR = 'QR';
    case MANUAL = 'MANUAL';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
