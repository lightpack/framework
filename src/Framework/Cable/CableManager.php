<?php

namespace Lightpack\Cable;

use Lightpack\Support\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Cable\Drivers\DatabaseCableDriver;
use Lightpack\Cable\Drivers\RedisCableDriver;

class CableManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in cable drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('database', function ($container) {
            $config = $container->get('config');
            $driver = new DatabaseCableDriver(
                $container->get('db'),
                $config->get('cable.database.table', 'cable_messages')
            );
            
            return new Cable($driver);
        });
        
        $this->register('redis', function ($container) {
            $config = $container->get('config');
            $driver = new RedisCableDriver(
                $container->get('redis'),
                $config->get('cable.redis.prefix', 'cable:')
            );
            
            return new Cable($driver);
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('cable.driver', 'database');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get cable driver instance
     */
    public function driver(?string $name = null): Cable
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Cable driver not found: {$name}";
    }
}
