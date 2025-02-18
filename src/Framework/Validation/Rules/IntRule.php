<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class IntRule
{
    private string $message = 'Must be an integer';

    public function __invoke($value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return true;
        }

        return false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
