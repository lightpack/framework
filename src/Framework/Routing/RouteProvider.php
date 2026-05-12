<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class RouteProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('route', function ($container) {
            return new RouteRegistry(
                $container
            );
        });

        $container->alias(RouteRegistry::class, 'route');
    }
}
