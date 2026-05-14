<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class ResponseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('response', function ($container) {
            return new Response;
        });

        $container->register('redirect', function ($container) {
            return new Redirect;
        });

        $container->alias(Response::class, 'response');
        $container->alias(Redirect::class, 'redirect');
    }
}
