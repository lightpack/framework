<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class FloatRule
{
    private string $message = 'Must be a floating point number';

    public function __invoke($value): bool
    {
        return is_float($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
