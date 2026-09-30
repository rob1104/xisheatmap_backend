<?php 

namespace App\Validators\ListaNominalRowValidator;

use App\Contracts\RowValidatorInterface;

class RowValidator implements RowValidatorInterface
{
    public function validate(array $rawRow): ValidationResult
    {
        // Validacion de Sección (> 0)
        $seccion = $this->parseNonNegativeInt($rawRow["seccion"] ?? null);
        if ($seccion == null || $seccion <= 0) {
            return ValidationResult::failure("la sección es obligatoria y debe ser un número entero o mayor a 0.");
        }

        // Validacion total nominal list (> 0)
        $totalLn = $this->parseNonNegativeInt($rawRow["total_ln"] ?? $rawRow["total_lista_nominal"] ?? null);
        if ($totalLn === null) {
            return ValidationResult::failure("el total de lista nominal es obligatorio y debe ser un número entero no negativo.");
        }

        // Validacion de Padrón Electoral (Opcional)
        $padronRaw = $rawRow["padron_electoral"] ?? null;
        $padron = $this->parseOptionalNonNegativeInt($padronRaw, 'padron_electoral');
        if ($padron === false) {
            return ValidationResult::failure("padron_electoral debe ser un entero no negativo o estar vacío");
        }

        // Validacion de Desglose de Género (Opcionales)
        $hombres = $this->parseOptionalNonNegativeInt($rawRow["hombres"] ?? null, "hombres");
        if ($hombres === false) {
            return ValidationResult::failure("El campo hombres debe ser un entero no negativo o estar vacío");
        }

        $mujeres = $this->parseOptionalNonNegativeInt($rawRow["mujeres"] ?? null, "mujeres");
        if ($mujeres === false) {
            return ValidationResult::failure("El campo mujeres debe ser un entero no negativo o estar vacío.");
        }

        // Validacion No Binario (Default 0)
        $noBinarioRaw = $this->parseOptionalNonNegativeInt($rawRow["no_binario"] ?? null, "no_binario");
        if ($noBinarioRaw === false) {
            return ValidationResult::failure("El campo no_binario debe ser un entero no negativo o estar vacío");
        }
        
        $noBinario = $noBinarioRaw ?? 0;

        return ValidationResult::success([
            'seccion'             => (string) $seccion,
            'total_lista_nominal' => $totalLn,
            'padron_electoral'    => $padron,
            'hombres'             => $hombres,
            'mujeres'             => $mujeres,
            'no_binario'          => $noBinario,
        ]);
    
    }

    /**
     * Parsea enteros no negativos (>= 0)
     * Retorna int si es válido, null si esta vacio o no numérico.
     */
    protected function parseNonNegativeInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === "" || !ctype_digit($trimmed)) {
            return null;
        }
        return (int) $trimmed;
    }

    /**
     * Parsea campos opcionales:
     * - Si esta vacío o es null -> retorna null
     * - Si tiene solo dígitos -> retorna int
     * - Si tiene solo caracteres no numéricos o no negativos -> retorna false
     */
    protected function parseOptionalNonNegativeInt(mixed $value, string $field): int|null|false
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === "") {
            return null;
        }

        if (!ctype_digit($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

}