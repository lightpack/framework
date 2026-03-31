<?php

namespace Lightpack\Auth;

use Lightpack\Support\ProviderInterface;
use Lightpack\Auth\Auth;
use Lightpack\Container\Container;

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
