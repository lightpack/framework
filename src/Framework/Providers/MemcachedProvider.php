<?php

namespace Lightpack\Providers;

use Lightpack\Cache\Memcached;
use Lightpack\Container\Container;

class MemcachedProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('memcached', function ($container) {
            $servers = $container->get('config')->get('cache.memcached.servers', []);

            return new Memcached($servers);
        });

        $container->alias(Memcached::class, 'memcached');
    }
}
