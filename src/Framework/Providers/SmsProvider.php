<?php

namespace Lightpack\Providers;

use Lightpack\Sms\Sms;
use Lightpack\Container\Container;
use Lightpack\Sms\SmsProviderInterface;
use Lightpack\Sms\SmsManager;
use Lightpack\Providers\ProviderInterface;

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
