<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class HasNumberRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must contain at least one numeric digit (0-9)';
    }

    public function __invoke($value): bool
    {
        return preg_match('/[0-9]/', $value) === 1;
    }
}
