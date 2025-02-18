<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class FloatRule
{
    private string $message = 'Must be a floating point number';

    public function __invoke($value): bool
    {
        if (is_float($value)) {
            return true;
        }

        if (is_string($value) && is_numeric($value) && strpos($value, '.') !== false) {
            return true;
        }

        return false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
