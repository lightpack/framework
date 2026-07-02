<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class EmailRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must be a valid email address';
        $this->langKey = 'validation.email';
    }

    public function __invoke($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
