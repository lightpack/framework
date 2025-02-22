<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class BetweenRule
{
    use ValidationMessageTrait;

    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max
    ) {
        $this->message = "Must be between {$min} and {$max}";
    }

    public function __invoke($value, array $data = []): bool
    {
        if (is_string($value)) {
            // If it's a numeric string, convert and validate as number
            if (preg_match('/^-?\d+$/', $value)) {
                $value = (int) $value;
            } elseif (is_numeric($value)) {
                $value = (float) $value;
            } else {
                // For non-numeric strings, validate length
                return mb_strlen($value) >= $this->min && mb_strlen($value) <= $this->max;
            }
        }

        if (!is_numeric($value)) {
            return false;
        }

        $value = (float) $value;
        return $value >= $this->min && $value <= $this->max;
    }
}
