<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class IpRule
{
    use ValidationMessageTrait;
    
    public function __construct(private ?string $version = null)
    {
        $this->message = 'Must be a valid IP address';

        $this->version = $version ?? '';
        if ($this->version === 'v4') {
            $this->message = 'Must be a valid IPv4 address';
        } elseif ($this->version === 'v6') {
            $this->message = 'Must be a valid IPv6 address';
        }
    }

    public function __invoke($value): bool
    {
        if ($this->version === 'v4') {
            return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        } elseif ($this->version === 'v6') {
            return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
