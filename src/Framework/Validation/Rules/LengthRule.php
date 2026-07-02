<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class LengthRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly int $length)
    {
        $this->message = "Length must be exactly {$length} characters";
        $this->langKey = 'validation.length';
        $this->messageParams = ['length' => $length];
    }

    public function __invoke($value): bool
    {
        if ($value === null) {
            return false;
        }

        return mb_strlen((string) $value) === $this->length;
    }
}
