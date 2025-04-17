<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Cable\Cable;
use Lightpack\Cable\Drivers\DatabaseDriver;
use Lightpack\Cable\Drivers\RedisDriver;

/**
 * Cable Provider
 * 
 * This provider registers the Cable service with the container.
 */
class CableProvider implements ProviderInterface
{
    /**
     * Register the service provider.
     */
    public function register(Container $container)
    {
        $container->register('cable', function ($container) {
            $config = $container->get('config');
            $driver = $this->getDriver($container, $config);
            
            return new Cable($driver);
        });

        $container->alias(Cable::class, 'cable');
    }
    
    /**
     * Get the appropriate driver based on configuration.
     */
    protected function getDriver(Container $container, $config)
    {
        $driver = $config->get('cable.driver', 'database');
        
        if ($driver === 'database') {
            return new DatabaseDriver(
                $container->get('db'),
                $config->get('cable.database.table', 'cable_messages')
            );
        }
        
        if ($driver === 'redis') {
            return new RedisDriver(
                $container->get('redis'),
                $config->get('cable.redis.prefix', 'cable:')
            );
        }
        
        throw new \Exception("Unsupported cable driver: {$driver}");
    }
}
