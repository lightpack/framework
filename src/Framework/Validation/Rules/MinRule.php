<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class MinRule
{
    use ValidationMessageTrait;

    public function __construct(
        private readonly int|float $min,
        private readonly ?string $type = null
    ) {
        $this->message = "Must not be less than {$min}";
    }

    public function __invoke($value): bool
    {
        // Skip validation for null and empty strings (use required rule for that)
        if ($value === null || $value === '') {
            return true;
        }

        // Arrays: validate count
        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        // If type was explicitly set (via string(), numeric(), etc.), use that
        if ($this->type === 'string') {
            return is_string($value) && mb_strlen($value) >= $this->min;
        }

        if ($this->type === 'numeric' || $this->type === 'int' || $this->type === 'float') {
            return is_numeric($value) && (float) $value >= $this->min;
        }

        // This ensures "500" validates as number 500, not string length 3
        if (is_numeric($value)) {
            return (float) $value >= $this->min;
        }

        // String length validation
        if (is_string($value)) {
            return mb_strlen($value) >= $this->min;
        }

        // For other types (objects, resources, etc.), fail validation
        return false;
    }
}
