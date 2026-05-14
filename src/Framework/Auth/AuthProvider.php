<?php

namespace Lightpack\Auth;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class AuthProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('auth', function ($container) {
            $config = $container->get('config')->get('auth.drivers');

            return new Auth('default', $config);
        });

        $container->alias(Auth::class, 'auth');
    }
}
