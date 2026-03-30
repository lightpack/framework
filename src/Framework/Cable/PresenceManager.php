<?php

namespace Lightpack\Cable;

use Lightpack\Support\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Cable\Drivers\DatabasePresenceDriver;
use Lightpack\Cable\Drivers\RedisPresenceDriver;

class PresenceManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in presence drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('database', function ($container) {
            $config = $container->get('config');
            $cable = $container->get('cable');
            
            $driver = new DatabasePresenceDriver(
                $container->get('db'),
                $config->get('cable.presence.database.table', 'cable_presence'),
                $config->get('cable.presence.database.timeout', 30)
            );
            
            return new Presence($cable, $driver);
        });
        
        $this->register('redis', function ($container) {
            $config = $container->get('config');
            $cable = $container->get('cable');
            
            $driver = new RedisPresenceDriver(
                $container->get('redis'),
                $config->get('cable.presence.redis.prefix', 'cable:presence:'),
                $config->get('cable.presence.redis.timeout', 30)
            );
            
            return new Presence($cable, $driver);
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('cable.presence.driver', 'database');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get presence driver instance
     */
    public function driver(?string $name = null): Presence
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Presence driver not found: {$name}";
    }
}
