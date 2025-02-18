<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class LengthRule
{
    private string $message;

    public function __construct(private readonly int $length) 
    {
        $this->message = "Must be exactly {$length} characters long";
    }

    public function __invoke($value): bool
    {
        if (!is_string($value) && !is_array($value)) {
            return false;
        }

        return strlen((string) $value) === $this->length;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
