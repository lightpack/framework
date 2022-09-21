<?php

namespace Lightpack\Providers;

use Lightpack\Http\Response;
use Lightpack\Container\Container;
use Lightpack\Utils\Url;

class ResponseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('response', function ($container) {
            return new Response(new Url);
        });

        $container->alias(Response::class, 'response');
    }
}
