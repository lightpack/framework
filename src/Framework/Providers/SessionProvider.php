<?php

namespace Lightpack\Providers;

use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Drivers\DefaultDriver;

class SessionProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('session', function ($container) {
            return new Session($this->getDriver());
        });
    }

    protected function getDriver(): DriverInterface
    {
        $sessionName = get_env('SESSION_DRIVER', 'default');
        $sessionDriver = get_env('SESSION_DRIVER', 'default');

        if ($sessionDriver === 'default') {
            return new DefaultDriver($sessionName);
        }

        if ($sessionDriver === 'array') {
            return new ArrayDriver($sessionName);
        }

        throw new \Exception('Session driver not found');
    }
}
