<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class SameRule
{
    private string $message;

    public function __construct(
        private readonly string $field,
        private readonly array $data
    ) {
        $this->message = "Must match {$field}";
    }

    public function __invoke($value): bool
    {
        return $value === $this->field;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
