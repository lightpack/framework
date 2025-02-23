<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class NumericRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Must be a numeric value';
    }

    public function __invoke($value): bool
    {
        return is_numeric($value);
    }
}
