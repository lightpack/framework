<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class UniqueRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Values must be unique';
    }

    public function __invoke($value): bool
    {
        if (!is_array($value)) {
            return true;
        }

        return count($value) === count(array_unique($value));
    }
}
