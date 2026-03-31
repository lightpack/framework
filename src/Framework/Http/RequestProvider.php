<?php

namespace Lightpack\Http;

use Lightpack\Support\ProviderInterface;
use Lightpack\Http\Request;
use Lightpack\Container\Container;

class RequestProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('request', function ($container) {
            return new Request();
        });

        $container->alias(Request::class, 'request');
    }
}
