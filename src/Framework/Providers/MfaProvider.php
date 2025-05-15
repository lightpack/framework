<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\Mfa;
use Lightpack\Mfa\Factor\NullMfa;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Utils\Otp;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        // register MFA providers
        $container->register('mfa', function ($container) {
            $service = new Mfa();

            // Email MFA
            $service->registerFactor(new EmailMfa(
                $container->get(Cache::class),
                $container->get(Config::class),
                new Otp()
            ));

            // Null MFA
            $service->registerFactor(new NullMfa);

            return $service;
        });

        $container->alias(Mfa::class, 'mfa');
    }
}
