<?php

namespace Lightpack\Cache;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class CacheProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('cache.manager', function ($container) {
            return new CacheManager($container);
        });

        $container->register('cache', function ($container) {
            return $container->get('cache.manager')->driver();
        });

        $container->alias(CacheManager::class, 'cache.manager');
        $container->alias(Cache::class, 'cache');
    }
}
