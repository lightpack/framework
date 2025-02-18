<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class RequiredIfRule
{
    private string $message;

    public function __construct(
        private readonly string $field,
        private readonly mixed $value = null
    ) {
        $this->message = $value === null
            ? "Required when {$field} is present"
            : "Required when {$field} is {$value}";
    }

    public function __invoke($value): bool
    {
        if ($this->value === null) {
            return $value !== null && $value !== '';
        }

        return $value !== null && $value !== '' && $value === $this->value;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
