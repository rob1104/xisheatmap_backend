<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRADOR = 'Administrador';
    case COORDINADOR_TERRITORIAL = 'Coordinador territorial';
    case GESTOR_SECCIONAL = 'Gestor seccional';
    case PRESIDENTE_COMITE = 'Presidente de comité';
    case INTEGRANTE_COMITE = 'Integrante de comité';
    case AFINES = 'Afines';

    // Alias de compatibilidad temporal para referencias previas
    public const COORDINADOR_SECTOR = self::COORDINADOR_TERRITORIAL;
    public const DESDOBLE = self::AFINES;

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Roles operativos de campo correspondientes a los niveles jerárquicos 2 al 5.
     * Excluye roles de administración/coordinación (niveles 0 y 1) y cualquier rol personalizado.
     *
     * @return array<string>
     */
    public static function fieldRoles(): array
    {
        return [
            self::GESTOR_SECCIONAL->value,
            self::PRESIDENTE_COMITE->value,
            self::INTEGRANTE_COMITE->value,
            self::AFINES->value,
        ];
    }

    public function level(): int
    {
        return match($this) {
            self::ADMINISTRADOR => 0,
            self::COORDINADOR_TERRITORIAL => 1,
            self::GESTOR_SECCIONAL => 2,
            self::PRESIDENTE_COMITE => 3,
            self::INTEGRANTE_COMITE => 4,
            self::AFINES => 5,
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
