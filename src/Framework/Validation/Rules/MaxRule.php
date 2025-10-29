<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class MaxRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly int|float $max) 
    {
        $this->message = "Must not be greater than {$max}";
    }

    public function __invoke($value): bool
    {
        // Handle arrays separately - empty arrays should be validated
        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        // For non-arrays, skip validation for empty values (use required() for that)
        // But allow '0' and 0 to pass through for numeric validation
        if (empty($value) && $value !== '0' && $value !== 0) {
            return false;
        }

        // Check numeric values BEFORE string length
        if (is_numeric($value)) {
            return (float) $value <= $this->max;
        }

        if (is_string($value)) {
            return mb_strlen((string) $value) <= $this->max;
        }

        return true;
    }
}
