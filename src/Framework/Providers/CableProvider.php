<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Cable\Cable;
use Lightpack\Cable\Drivers\DatabaseCableDriver;
use Lightpack\Cable\Presence;
use Lightpack\Cable\Drivers\DatabasePresenceDriver;
use Lightpack\Cable\Drivers\RedisCableDriver;
use Lightpack\Cable\Drivers\RedisPresenceDriver;

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
        
        // Register Presence service
        $container->register('presence', function ($container) {
            $cable = $container->get('cable');
            $config = $container->get('config');
            $driver = $this->getPresenceDriver($container, $config);
            
            return new Presence($cable, $driver);
        });
        
        $container->alias(Presence::class, 'presence');
    }
    
    /**
     * Get the appropriate driver based on configuration.
     */
    protected function getDriver(Container $container, $config)
    {
        $driver = $config->get('cable.driver', 'database');
        
        if ($driver === 'database') {
            return new DatabaseCableDriver(
                $container->get('db'),
                $config->get('cable.database.table', 'cable_messages')
            );
        }
        
        if ($driver === 'redis') {
            return new RedisCableDriver(
                $container->get('redis'),
                $config->get('cable.redis.prefix', 'cable:')
            );
        }
        
        throw new \Exception("Unsupported cable driver: {$driver}");
    }
    
    /**
     * Get the appropriate presence driver based on configuration.
     */
    protected function getPresenceDriver(Container $container, $config)
    {
        $driver = $config->get('cable.presence.driver', 'database');
        
        if ($driver === 'database') {
            return new DatabasePresenceDriver(
                $container->get('db'),
                $config->get('cable.presence.database.table', 'cable_presence'),
                $config->get('cable.presence.database.timeout', 30)
            );
        }
        
        if ($driver === 'redis') {
            return new RedisPresenceDriver(
                $container->get('redis'),
                $config->get('cable.presence.redis.prefix', 'cable:presence:'),
                $config->get('cable.presence.redis.timeout', 30)
            );
        }
        
        throw new \Exception("Unsupported presence driver: {$driver}");
    }
}
