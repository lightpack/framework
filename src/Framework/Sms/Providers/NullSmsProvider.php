<?php

namespace Lightpack\Sms\Providers;

use Lightpack\Sms\SmsProviderInterface;

class NullSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message, array $options = []): bool
    {
        // Does nothing, always succeeds
        return true;
    }
}
