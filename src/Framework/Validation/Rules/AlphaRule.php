<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class AlphaRule
{
    private string $message = 'Must contain only alphabetic characters';

    public function __invoke($value): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\p{M}]+$/u', $value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
