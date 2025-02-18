<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class NotInRule
{
    private string $message;

    public function __construct(private readonly array $values) 
    {
        $this->message = "Must not be one of: " . implode(', ', $values);
    }

    public function __invoke($value): bool
    {
        return !in_array($value, $this->values, true);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
