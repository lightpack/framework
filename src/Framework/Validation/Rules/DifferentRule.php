<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class DifferentRule
{
    private string $message;

    public function __construct(
        private readonly string $field,
        private readonly array $data
    ) {
        $this->message = "Must be different from {$field}";
    }

    public function __invoke($value): bool
    {
        return $value !== ($this->data[$this->field] ?? null);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
