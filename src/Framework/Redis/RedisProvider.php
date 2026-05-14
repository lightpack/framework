<?php

namespace Lightpack\Redis;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

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
