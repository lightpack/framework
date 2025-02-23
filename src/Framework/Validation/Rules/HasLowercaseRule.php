<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class HasLowercaseRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must contain at least one lowercase letter';
    }

    public function __invoke($value): bool
    {
        return preg_match('/[a-z]/', $value) === 1;
    }
}
