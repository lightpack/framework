<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class StringRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Must be a string value';
    }

    public function __invoke($value): bool
    {
        return is_string($value);
    }
}
