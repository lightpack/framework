<?php

namespace Lightpack\Providers;

use Lightpack\Http\Response;
use Lightpack\Container\Container;
use Lightpack\Http\Redirect;

class ResponseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('response', function ($container) {
            return new Response();
        });

        $container->register('redirect', function ($container) {
            return new Redirect();
        });

        $container->alias(Response::class, 'response');
        $container->alias(Redirect::class, 'redirect');
    }
}
