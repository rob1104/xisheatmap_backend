<?php 

namespace App\Contracts;

use App\Validators\ListaNominalRowValidator\ValidationResult;

interface RowValidatorInterface {
    /**
     * Valida y sanitiza los atributos de una fila cruda.
     */

    public function validate(array $rawRow): ValidationResult;
}
