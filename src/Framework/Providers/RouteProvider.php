<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Routing\RouteRegistry;

class RouteProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('route', function ($container) {
            return new RouteRegistry(
                $container->get('request')
            );
        });

        $container->alias(RouteRegistry::class, 'route');
    }
}
