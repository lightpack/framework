<?php

namespace Lightpack\Providers;

use Lightpack\Sms\Sms;
use Lightpack\Container\Container;
use Lightpack\Sms\SmsProviderInterface;
use Lightpack\Sms\Providers\LogSmsProvider;
use Lightpack\Sms\Providers\NullSmsProvider;
use Lightpack\Sms\Providers\TwilioProvider;
use Lightpack\Providers\ProviderInterface;

class SmsProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('sms', function ($container) {
            $type = config('sms.provider') ?? 'null';

            $provider = match ($type) {
                'null'   => new NullSmsProvider(),
                'log'    => new LogSmsProvider(
                    $container->get('logger')
                ),
                'twilio' => new TwilioProvider(
                    $container->get('logger'),
                    config('sms.twilio') ?? []
                ),
                default => throw new \Exception("Unknown SMS provider: {$type}"),
            };

            return new Sms($provider);
        });

        $container->alias(Sms::class, 'sms');
        $container->alias(SmsProviderInterface::class, 'sms');
    }
}
