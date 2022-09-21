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

        $container->alias(Session::class, 'session');
    }

    protected function getDriverClassname(): string
    {
        $sessionDriver = get_env('SESSION_DRIVER', 'default');

        if ($sessionDriver === 'default') {
            return DefaultDriver::class;
        }

        if ($sessionDriver === 'array') {
            return ArrayDriver::class;
        }

        throw new \Exception('Session driver not found');
    }

    protected function getDriver(): DriverInterface
    {
        $sessionName = get_env('SESSION_NAME', 'lightpack_session');

        $driver = $this->getDriverClassname();

        return new $driver($sessionName);
    }
}
