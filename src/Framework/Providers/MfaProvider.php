<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\Mfa;
use Lightpack\Mfa\Factor\NullMfa;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Mfa\Otp;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        // register 'otp'
        $container->register('otp', function($container) {
            return new Otp(
                $container->get(Config::class)
            );
        });
        $container->alias('otp', Otp::class);

        // register MFA providers
        $container->register('mfa', function ($container) {
            $service = new Mfa();

            // Email MFA
            $service->registerFactor(new EmailMfa(
                $container->get(Cache::class),
                $container->get(Config::class),
                $container->get(Otp::class)
            ));

            // Null MFA
            $service->registerFactor(new NullMfa);

            return $service;
        });

        $container->alias(Mfa::class, 'mfa');
    }
}
