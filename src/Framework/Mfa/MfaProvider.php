<?php

namespace Lightpack\Mfa;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

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
