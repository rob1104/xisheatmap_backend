<?php

namespace App\Validators\ListaNominalRowValidator;

class ValidationResult 
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?array $sanitizedData = null,
        public readonly ?string $errorMessage = null,
    ){}

    public static function success(array $data): self
    {
        return new self(isValid: true, sanitizedData: $data);
    }

    public static function failure(string $message): self
    {
        return new self(isValid: false, errorMessage: $message);
    }
}