<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class MinRule
{
    private string $message;

    public function __construct(private readonly int|float $min) 
    {
        $this->message = "Must not be less than {$min}";
    }

    public function __invoke($value): bool
    {
        if (is_string($value) || is_array($value)) {
            return strlen((string) $value) >= $this->min;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return (float) $value >= $this->min;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
