<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class MinRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly int|float $min)
    {
        $this->message = "Must not be less than {$min}";
    }

    public function __invoke($value): bool
    {
        // Handle arrays separately - empty arrays should be validated
        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        // For non-arrays, skip validation for empty values (use required() for that)
        // But allow '0' and 0 to pass through for numeric validation
        if (empty($value) && $value !== '0' && $value !== 0) {
            return false;
        }

        // Check numeric values BEFORE string length
        // This ensures form inputs like "47" are validated as numbers
        if (is_numeric($value)) {
            return (float) $value >= $this->min;
        }

        if (is_string($value)) {
            return mb_strlen((string) $value) >= $this->min;
        }

        return true;
    }
}
