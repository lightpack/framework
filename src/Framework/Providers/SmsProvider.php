<?php

namespace Lightpack\Providers;

use Lightpack\Sms\Sms;
use Lightpack\Container\Container;
use Lightpack\Sms\SmsProviderInterface;
use Lightpack\Sms\Providers\LogSmsProvider;
use Lightpack\Sms\Providers\NullSmsProvider;
use Lightpack\Providers\ProviderInterface;

class SmsProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('sms', function ($container) {
            $config = $container->get('config')->get('sms');
            $type = $config['provider'] ?? 'null';

            $provider = match ($type) {
                'log'  => new LogSmsProvider($container->get('logger')),
                'null' => new NullSmsProvider(),
                default => throw new \Exception("Unknown SMS provider: {$type}"),
            };

            return new Sms($provider);
        });

        $container->alias(Sms::class, 'sms');
        $container->alias(SmsProviderInterface::class, 'sms');
    }
}
