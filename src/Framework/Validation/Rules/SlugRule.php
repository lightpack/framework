<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class SlugRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Must be a valid URL slug';
    }

    public function __invoke($value): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }
}
