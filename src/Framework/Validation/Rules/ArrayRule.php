<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class ArrayRule
{
    private string $message = 'Must be an array';

    public function __invoke($value): bool
    {
        return is_array($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
