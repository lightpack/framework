<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class NullableRule
{
    private string $message = '';

    public function __invoke($value): bool
    {
        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
