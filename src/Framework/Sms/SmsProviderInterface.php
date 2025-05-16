<?php

namespace Lightpack\Sms;

interface SmsProviderInterface
{
    public function send(string $phone, string $message, array $options = []): bool;
}
