<?php

namespace Lightpack\Mfa;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Mfa\MfaManager;

class MfaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mfa.manager', function ($container) {
            return new MfaManager($container);
        });

        $container->register('mfa', function ($container) {
            return $container->get('mfa.manager')->driver();
        });

        $container->alias(MfaManager::class, 'mfa.manager');
        $container->alias(MfaInterface::class, 'mfa');
    }
}
