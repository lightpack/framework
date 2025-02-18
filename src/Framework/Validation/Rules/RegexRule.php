<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class RegexRule
{
    private string $message;

    public function __construct(private readonly string $pattern) 
    {
        $this->message = "Must match pattern: {$pattern}";
    }

    public function __invoke($value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
