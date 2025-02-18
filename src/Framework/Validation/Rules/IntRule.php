<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class IntRule
{
    private string $message = 'Must be an integer';

    public function __invoke($value): bool
    {
        return is_int($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
