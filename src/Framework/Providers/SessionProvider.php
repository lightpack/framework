<?php

namespace Lightpack\Providers;

use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Drivers\CacheDriver;
use Lightpack\Session\Drivers\DefaultDriver;

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
                $session->configureCookie();
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
        $sessionDriver = $container->get('config')->get('session.driver', 'default');

        if ($sessionDriver === 'default') {
            return new DefaultDriver();
        }

        if($sessionDriver === 'array') {
            return new ArrayDriver;
        }

        if ($sessionDriver === 'cache') {
            return new CacheDriver(
                $container->get('cache'),
                $container->get('cookie'),
                $container->get('config')
            );
        }

        throw new \Exception('Session driver not found: ' . $sessionDriver);
    }
}
