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

            /** @var \Lightpack\Http\Request */
            $request = $container->get('request');
            $driver = $this->getDriver();
            $session = new Session($driver, get_env('SESSION_NAME'));

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
