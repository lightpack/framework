<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class AlphaRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must contain only alphabetic characters';
        $this->langKey = 'validation.alpha';
    }

    public function __invoke($value): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\p{M}]+$/u', $value);
    }
}
