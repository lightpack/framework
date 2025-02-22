<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class IntRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
       $this->message = 'Must be an integer';
    }

    public function __invoke($value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return true;
        }

        return false;
    }
}
