<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Redis\Redis;

class RedisProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        // Register Redis client
        $container->register('redis', function ($container) {
            $config = $container->get('config');
            $redisConfig = $config->get('redis.default', []);
            
            return new Redis($redisConfig);
        });

        $container->alias(Redis::class, 'redis');
    }
}
