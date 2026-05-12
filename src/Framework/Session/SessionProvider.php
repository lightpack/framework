<?php

namespace Lightpack\Session;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class SessionProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('session.manager', function ($container) {
            return new SessionManager($container);
        });

        $container->register('session', function ($container) {
            return $container->get('session.manager')->driver();
        });

        $container->alias(SessionManager::class, 'session.manager');
        $container->alias(Session::class, 'session');
    }
}
