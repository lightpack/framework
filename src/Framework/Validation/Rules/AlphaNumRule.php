<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class AlphaNumRule
{
    use ValidationMessageTrait;

    public function __construct()
    {
        $this->message = 'Must contain only letters and numbers';
        $this->langKey = 'validation.alpha_num';
    }

    public function __invoke($value): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\p{M}\p{N}]+$/u', $value);
    }
}
