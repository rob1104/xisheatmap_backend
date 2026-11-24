<?php

namespace App\Exceptions;

use RuntimeException;

class ParticipacionAuthorizationException extends RuntimeException
{
    public function __construct(
        string $message = 'No tienes permiso territorial para operar sobre esta sección electoral.'
    ) {
        parent::__construct($message, 403);
    }
}
