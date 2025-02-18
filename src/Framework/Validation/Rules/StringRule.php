<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class StringRule
{
    private string $message = 'Must be a string value';

    public function __invoke($value): bool
    {
        return is_string($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
