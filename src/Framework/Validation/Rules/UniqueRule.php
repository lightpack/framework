<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class UniqueRule
{
    private string $message = 'Values must be unique';

    public function __invoke($value): bool
    {
        if (!is_array($value)) {
            return true;
        }

        return count($value) === count(array_unique($value));
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
