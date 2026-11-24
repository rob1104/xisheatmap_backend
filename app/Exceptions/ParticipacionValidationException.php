<?php

namespace App\Exceptions;

use RuntimeException;

class ParticipacionValidationException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = []
    ) {
        parent::__construct($message, 422);
    }
}
