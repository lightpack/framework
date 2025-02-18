<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class EmailRule
{
    private string $message = 'Must be a valid email address';

    public function __invoke($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
