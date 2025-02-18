<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class RequiredRule
{
    private string $message = 'This field is required';

    public function __invoke($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
