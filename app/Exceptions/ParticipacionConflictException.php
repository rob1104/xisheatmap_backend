<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class ParticipacionConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $folioExistente,
        public readonly CarbonInterface $fechaServidor,
        string $message = 'El ciudadano ya cuenta con una participación registrada en esta jornada.'
    ) {
        parent::__construct($message, 409);
    }
}
