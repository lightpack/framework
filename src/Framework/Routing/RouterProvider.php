<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class RouterProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('router', function ($container) {
            return new Router($container->get('route'));
        });

        $container->alias(Router::class, 'router');
    }
}
