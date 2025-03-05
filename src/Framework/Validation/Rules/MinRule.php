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
        if (empty($value)) {
            return false;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        if (is_string($value)) {
            return mb_strlen((string) $value) >= $this->min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $this->min;
        }

        return true;
    }
}
