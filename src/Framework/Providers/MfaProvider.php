<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\Mfa;
use Lightpack\Mfa\Factor\NullMfa;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Mfa\Factor\SmsMfa;
use Lightpack\Utils\Otp;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        // register MFA providers
        $container->register('mfa', function ($container) {
            $service = new Mfa();

            // Null MFA
            $service->registerFactor(new NullMfa);

            // Email MFA
            $service->registerFactor(new EmailMfa(
                $container->get('cache'),
                $container->get('config'),
                new Otp()
            ));

            // Sms MFA
            $service->registerFactor(new SmsMfa(
                $container->get('cache'),
                $container->get('config'),
                new Otp(),
                $container->get('sms')
            ));

            return $service;
        });

        $container->alias(Mfa::class, 'mfa');
    }
}
