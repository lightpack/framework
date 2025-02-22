<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class UrlRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Must be a valid URL';
    }

    public function __invoke($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
