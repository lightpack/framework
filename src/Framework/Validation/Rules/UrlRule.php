<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class UrlRule
{
    private string $message = 'Must be a valid URL';

    public function __invoke($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
