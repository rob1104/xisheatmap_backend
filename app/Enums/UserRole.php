<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRADOR = 'Administrador';
    case COORDINADOR_SECTOR = 'Coordinador de sector';
    case GESTOR_SECCIONAL = 'Gestor seccional';
    case PRESIDENTE_COMITE = 'Presidente de comité';
    case INTEGRANTE_COMITE = 'Integrante de comité';
    case DESDOBLE = 'Desdoble';

    public function level(): int
    {
        return match($this) {
            self::ADMINISTRADOR => 0,
            self::COORDINADOR_SECTOR => 1,
            self::GESTOR_SECCIONAL => 2,
            self::PRESIDENTE_COMITE => 3,
            self::INTEGRANTE_COMITE => 4,
            self::DESDOBLE => 5,
        };
    }

    public static function fromLevel(int $level): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->level() === $level) {
                return $case;
            }
        }
        return null;
    }

    public function parentLevel(): ?int
    {
        $level = $this->level();
        return $level > 0 ? $level - 1 : null;
    }
}
