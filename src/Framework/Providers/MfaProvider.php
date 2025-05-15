<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\Mfa;
use Lightpack\Mfa\Factor\NullMfa;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Cache\Cache;
use Lightpack\Mail\Mail;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mfa', function ($container) {
            $service = new Mfa();

            // Email MFA
            $service->registerFactor(new EmailMfa(
                new Mail,
                $container->get(Cache::class)
            ));

            // Null MFA
            $service->registerFactor(new NullMfa());

            return $service;
        });

        $container->alias(Mfa::class, 'mfa');
    }
}
