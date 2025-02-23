<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class HasUppercaseRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must contain at least one uppercase letter';
    }

    public function __invoke($value): bool
    {
        return preg_match('/[A-Z]/', $value) === 1;
    }
}
