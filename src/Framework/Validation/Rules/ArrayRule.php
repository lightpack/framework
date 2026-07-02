<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class ArrayRule
{
    use ValidationMessageTrait;

    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null
    ) {
        if ($min !== null && $max !== null) {
            $this->message = "Must have between {$min} and {$max} items";
            $this->langKey = 'validation.array_between';
            $this->messageParams = ['min' => $min, 'max' => $max];
        } elseif ($min !== null) {
            $this->message = "Must have at least {$min} items";
            $this->langKey = 'validation.array_min';
            $this->messageParams = ['min' => $min];
        } elseif ($max !== null) {
            $this->message = "Cannot have more than {$max} items";
            $this->langKey = 'validation.array_max';
            $this->messageParams = ['max' => $max];
        } else {
            $this->message = 'Must be an array';
            $this->langKey = 'validation.array';
        }
    }

    public function __invoke($value, array $data = []): bool
    {
        if (! is_array($value)) {
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
}
