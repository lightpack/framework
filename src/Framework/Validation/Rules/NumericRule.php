<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class NumericRule
{
    private string $message = 'Must be a numeric value';

    public function __invoke($value): bool
    {
        return is_numeric($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
