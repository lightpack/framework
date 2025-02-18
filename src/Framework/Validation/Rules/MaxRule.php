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
        if ($value === null) {
            return false;
        }
        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        if (!is_numeric($value)) {
            return mb_strlen((string) $value) <= $this->max;
        }

        return (float) $value <= $this->max;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
