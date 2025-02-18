<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class MaxRule
{
    private string $message;

    public function __construct(private readonly int|float $max) 
    {
        $this->message = "Must not be greater than {$max}";
    }

    public function __invoke($value): bool
    {
        if (is_string($value) || is_array($value)) {
            return strlen((string) $value) <= $this->max;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return (float) $value <= $this->max;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
