<?php

namespace Lightpack\Sms;

class Sms
{
    protected $provider;

    public function __construct(SmsProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function send(string $phone, string $message, array $options = []): bool
    {
        return $this->provider->send($phone, $message, $options);
    }
}
