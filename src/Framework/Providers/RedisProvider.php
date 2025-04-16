<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Redis\Redis;
use Lightpack\Cache\Drivers\RedisDriver as CacheRedisDriver;
use Lightpack\Session\Drivers\RedisDriver as SessionRedisDriver;

class RedisProvider
{
    public function register(Container $container)
    {
        // Register Redis client
        $container->register('redis', function ($container) {
            $config = $container->get('config');
            $redisConfig = $config->get('redis.default', []);
            
            return new Redis($redisConfig);
        });
        
        // Register Redis cache driver
        $container->register('cache.driver.redis', function ($container) {
            $config = $container->get('config');
            $redis = $container->get('redis');
            $prefix = $config->get('redis.cache.prefix', 'cache:');
            
            return new CacheRedisDriver($redis, $prefix);
        });
        
        // Register Redis session driver
        $container->register('session.driver.redis', function ($container) {
            $config = $container->get('config');
            $redis = $container->get('redis');
            $prefix = $config->get('redis.session.prefix', 'session:');
            $lifetime = $config->get('redis.session.lifetime', 7200);
            $name = $config->get('session.name', 'lightpack_session');
            
            return new SessionRedisDriver($redis, $name, $lifetime, $prefix);
        });
    }
}
