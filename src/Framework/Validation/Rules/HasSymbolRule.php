<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class HasSymbolRule
{
    use ValidationMessageTrait;

    private const SPECIAL_CHARS = '!@#$%^&*(),.?":{}|<>-_+=[]\\;\'`~';

    public function __construct()
    {
        $this->message = 'Must contain at least one special character (' . self::SPECIAL_CHARS . ')';
    }

    public function __invoke($value): bool
    {
        return preg_match('/[' . preg_quote(self::SPECIAL_CHARS, '/') . ']/', $value) === 1;
    }
}
