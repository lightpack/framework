<?php

namespace Lightpack\Sms;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class SmsProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('sms.manager', function ($container) {
            return new SmsManager($container);
        });

        $container->register('sms', function ($container) {
            return $container->get('sms.manager')->driver();
        });

        $container->alias(SmsManager::class, 'sms.manager');
        $container->alias(Sms::class, 'sms');
        $container->alias(SmsProviderInterface::class, 'sms');
    }
}
