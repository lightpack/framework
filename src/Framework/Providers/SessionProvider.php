<?php

namespace Lightpack\Providers;

use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Drivers\CacheDriver;
use Lightpack\Session\Drivers\DefaultDriver;
use Lightpack\Session\Drivers\RedisDriver;
use Lightpack\Session\Drivers\EncryptedDriver;

class SessionProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('session', function ($container) {
            /** @var \Lightpack\Http\Request */
            $request = $container->get('request');
            $driver = $this->getDriver($container);
            $session = new Session($driver, $container->get('config'));

            if(!$driver instanceof ArrayDriver) {
                $session->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? 'Lightpack PHP');
                $driver->start();
            }

            if($request->isGet()) {
                $session->set('_previous_url', $request->fullUrl());
            }

            return $session;
        });

        $container->alias(Session::class, 'session');
    }

    protected function getDriver(Container $container): DriverInterface
    {
        $config = $container->get('config');
        $sessionDriver = $config->get('session.driver', 'file');
        $encrypt = $config->get('session.encrypt', false);

        $driver = match($sessionDriver) {
            'array' => new ArrayDriver(),
            'file' => new DefaultDriver($config),
            'cache' => new CacheDriver($container->get('cache'), $container->get('cookie'), $config),
            'redis' => $this->createRedisDriver($container),
            default => throw new \Exception('Session driver not found: ' . $sessionDriver)
        };

        if ($encrypt && !$driver instanceof ArrayDriver) {
            return new EncryptedDriver($driver, $container->get('crypto'));
        }

        return $driver;
    }
    
    /**
     * Create a Redis session driver instance
     */
    protected function createRedisDriver(Container $container): RedisDriver
    {
        $config = $container->get('config');
        $redis = $container->get('redis');
        $prefix = $config->get('redis.session.prefix', 'session:');
        $lifetime = $config->get('redis.session.lifetime', 7200);
        $name = $config->get('session.name', 'lightpack_session');
        
        return new RedisDriver($redis, $name, $lifetime, $prefix);
    }
}
