<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class SlugRule
{
    private string $message = 'Must be a valid URL slug';

    public function __invoke($value): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
