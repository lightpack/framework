<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Mfa\MfaService;
use Lightpack\Mfa\EmailMfaFactor;
use Lightpack\Cache\Cache;
use Lightpack\Mail\Mail;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mfa', function ($container) {
            $service = new MfaService();

            // Email MFA
            $service->registerFactor(new EmailMfaFactor(
                $container->get(Mail::class),
                $container->get(Cache::class)
            ));

            // Null MFA
            $service->registerFactor(new \Lightpack\Mfa\NullMfaFactor());

            return $service;
        });

        $container->alias(MfaService::class, 'mfa');
    }
}
