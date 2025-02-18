<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class AlphaNumRule
{
    private string $message = 'Must contain only letters and numbers';

    public function __invoke($value): bool
    {
        return ctype_alnum($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
