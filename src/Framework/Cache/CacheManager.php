<?php

namespace Lightpack\Cache;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Cache\Drivers\FileDriver;
use Lightpack\Cache\Drivers\NullDriver;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Cache\Drivers\RedisDriver;
use Lightpack\Cache\Drivers\DatabaseDriver;

class CacheManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in cache drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('null', function ($container) {
            return new Cache(new NullDriver);
        });
        
        $this->register('array', function ($container) {
            return new Cache(new ArrayDriver);
        });
        
        $this->register('file', function ($container) {
            return new Cache(new FileDriver(DIR_STORAGE . '/cache'));
        });
        
        $this->register('database', function ($container) {
            return new Cache(new DatabaseDriver($container->get('db')));
        });
        
        $this->register('redis', function ($container) {
            $config = $container->get('config');
            $redis = $container->get('redis');
            $prefix = $config->get('redis.cache.prefix', 'cache:');
            
            return new Cache(new RedisDriver($redis, $prefix));
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = get_env('CACHE_DRIVER', 'null');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get cache driver instance
     */
    public function driver(?string $name = null): Cache
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Cache driver not found: {$name}";
    }
}
