<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class ArrayRule
{
    private string $message;

    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null
    ) {
        if ($min !== null && $max !== null) {
            $this->message = "Must have between {$min} and {$max} items";
        } elseif ($min !== null) {
            $this->message = "Must have at least {$min} items";
        } elseif ($max !== null) {
            $this->message = "Cannot have more than {$max} items";
        } else {
            $this->message = 'Must be an array';
        }
    }

    public function __invoke($value, array $data = []): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $count = count($value);

        if ($this->min !== null && $count < $this->min) {
            return false;
        }

        if ($this->max !== null && $count > $this->max) {
            return false;
        }

        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
