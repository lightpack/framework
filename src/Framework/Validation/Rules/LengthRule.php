<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class LengthRule
{
    private string $message;

    public function __construct(private readonly int $length) 
    {
        $this->message = "Length must be exactly {$length} characters";
    }

    public function __invoke($value): bool
    {
        if ($value === null) {
            return false;
        }
        return mb_strlen((string) $value) === $this->length;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
