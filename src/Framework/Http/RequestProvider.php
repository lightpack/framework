<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class RequestProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('request', function ($container) {
            return new Request;
        });

        $container->alias(Request::class, 'request');
    }
}
