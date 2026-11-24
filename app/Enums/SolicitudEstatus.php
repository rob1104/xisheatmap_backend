<?php

namespace App\Enums;

enum SolicitudEstatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case EN_PROCESO = 'EN_PROCESO';
    case ATENDIDA = 'ATENDIDA';
    case CANCELADA = 'CANCELADA';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::ATENDIDA, self::CANCELADA], true);
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return match ($this) {
            self::PENDIENTE => in_array($next, [self::EN_PROCESO, self::ATENDIDA, self::CANCELADA], true),
            self::EN_PROCESO => in_array($next, [self::ATENDIDA, self::CANCELADA], true),
            self::ATENDIDA, self::CANCELADA => false,
        };
    }
}
