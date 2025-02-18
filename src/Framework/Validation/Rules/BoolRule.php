<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class BoolRule
{
    private string $message = 'Must be a boolean value';

    public function __invoke($value): bool
    {
        return is_bool($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
