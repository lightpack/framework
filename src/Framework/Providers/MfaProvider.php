<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\Mfa;
use Lightpack\Mfa\Factor\NullMfa;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Cache\Cache;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mfa', function ($container) {
            $service = new Mfa();

            // Email MFA
            $service->registerFactor(new EmailMfa(
                $container->get(Cache::class),
                config('mfa.email.ttl')
            ));

            // Null MFA
            $service->registerFactor(new NullMfa);

            return $service;
        });

        $container->alias(Mfa::class, 'mfa');
    }
}
