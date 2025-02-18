<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class AlphaRule
{
    private string $message = 'Must contain only alphabetic characters';

    public function __invoke($value): bool
    {
        return ctype_alpha($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
