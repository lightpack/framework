<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class CustomRule
{
    private string $message;
    private $callback;

    public function __construct(callable $callback, string $message = 'Validation failed') 
    {
        $this->callback = $callback;
        $this->message = $message;
    }

    public function __invoke($value): bool
    {
        return ($this->callback)($value);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
