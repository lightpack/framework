<?php

namespace Lightpack\Providers;

use Lightpack\Http\Response;
use Lightpack\Container\Container;
use Lightpack\Http\Redirect;
use Lightpack\Utils\Url;

class ResponseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('response', function ($container) {
            return new Response();
        });

        $container->register('redirect', function ($container) {
            $redirect = new Redirect();

            return $container->call($redirect, 'boot');
        });

        $container->alias(Response::class, 'response');
        $container->alias(Redirect::class, 'redirect');
    }
}
