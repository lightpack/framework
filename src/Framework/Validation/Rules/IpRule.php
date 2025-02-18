<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class IpRule
{
    private string $message = 'Must be a valid IP address';

    public function __invoke($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
