<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class NotInRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly array $values)
    {
        $this->message = 'Must not be one of: ' . implode(', ', $values);
        $this->langKey = 'validation.not_in';
        $this->messageParams = ['values' => implode(', ', $values)];
    }

    public function __invoke($value): bool
    {
        return ! in_array($value, $this->values, true);
    }
}
