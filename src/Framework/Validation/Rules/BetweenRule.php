<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class BetweenRule
{
    private string $message;

    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max
    ) {
        $this->message = "Must be between {$min} and {$max}";
    }

    public function __invoke($value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $value = (float) $value;
        return $value >= $this->min && $value <= $this->max;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
