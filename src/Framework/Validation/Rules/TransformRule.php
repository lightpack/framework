<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class TransformRule
{
    private $callback;

    public function __construct(callable $callback) 
    {
        $this->callback = $callback;
    }

    public function __invoke($value): bool
    {
        return true;
    }

    public function transform($value)
    {
        return ($this->callback)($value);
    }

    public function getMessage(): string 
    {
        return '';
    }
}
