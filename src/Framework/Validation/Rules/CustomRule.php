<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class CustomRule
{
    use ValidationMessageTrait;

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
}
