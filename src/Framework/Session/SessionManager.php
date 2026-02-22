<?php

namespace Lightpack\Session;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Drivers\CacheDriver;
use Lightpack\Session\Drivers\DefaultDriver;
use Lightpack\Session\Drivers\RedisDriver;
use Lightpack\Session\Drivers\EncryptedDriver;

class SessionManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in session drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('array', function ($container) {
            $driver = new ArrayDriver();
            return $this->createSession($container, $driver, false);
        });
        
        $this->register('file', function ($container) {
            $config = $container->get('config');
            $driver = new DefaultDriver($config);
            return $this->createSession($container, $driver);
        });
        
        $this->register('cache', function ($container) {
            $config = $container->get('config');
            $driver = new CacheDriver(
                $container->get('cache'),
                $container->get('cookie'),
                $config
            );
            return $this->createSession($container, $driver);
        });
        
        $this->register('redis', function ($container) {
            $config = $container->get('config');
            $redis = $container->get('redis');
            $prefix = $config->get('redis.session.prefix', 'session:');
            $lifetime = $config->get('redis.session.lifetime', 7200);
            $name = $config->get('session.name', 'lightpack_session');
            
            $driver = new RedisDriver($redis, $name, $lifetime, $prefix);
            return $this->createSession($container, $driver);
        });
    }
    
    /**
     * Create session instance with driver
     */
    protected function createSession(Container $container, DriverInterface $driver, bool $start = true): Session
    {
        $config = $container->get('config');
        $encrypt = $config->get('session.encrypt', false);
        $isArrayDriver = $driver instanceof ArrayDriver;
        
        // Apply encryption wrapper if enabled
        if ($encrypt && !$isArrayDriver) {
            $driver = new EncryptedDriver($driver, $container->get('crypto'));
        }
        
        $session = new Session($driver, $config);
        
        // Start session and set user agent (except for ArrayDriver)
        if ($start && !$isArrayDriver) {
            $session->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? 'Lightpack PHP');
            $driver->start();
        }
        
        // Store previous URL for GET requests
        $request = $container->get('request');
        if ($request->isGet()) {
            $session->set('_previous_url', $request->fullUrl());
        }
        
        return $session;
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('session.driver', 'file');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get session driver instance
     */
    public function driver(?string $name = null): Session
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Session driver not found: {$name}";
    }
}
