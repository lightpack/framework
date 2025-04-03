<?php

namespace Lightpack\Providers;

use Lightpack\Cache\Cache;
use Lightpack\Cache\DriverInterface;
use Lightpack\Cache\Drivers\FileDriver;
use Lightpack\Cache\Drivers\NullDriver;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Cache\Drivers\DatabaseDriver;
use Lightpack\Cache\Drivers\MemcachedDriver;
use Lightpack\Container\Container;

class CacheProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('cache', function ($container) {
            $driver = $this->getDriver($container);

            return new Cache($driver);
        });

        $container->alias(Cache::class, 'cache');
    }

    protected function getDriver(Container $container): DriverInterface
    {
        $cacheDriver = get_env('CACHE_DRIVER', 'null');

        if ($cacheDriver === 'null') {
            return new NullDriver;
        }

        if($cacheDriver === 'array') {
            return new ArrayDriver;
        }

        if ($cacheDriver === 'file') {
            $cacheDir = $container->get('config')->get('storage.cache');
            return new FileDriver($cacheDir);
        }

        if ($cacheDriver === 'database') {
            return new DatabaseDriver($container->get('db'));
        }

        if ($cacheDriver === 'memcached') {
            return new MemcachedDriver($container->get('memcached'));
        }

        throw new \Exception('Cache driver not found: ' . $cacheDriver);
    }
}
