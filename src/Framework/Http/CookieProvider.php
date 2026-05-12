<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class CookieProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('cookie', function ($container) {
            $secret = get_env('APP_KEY', 'secret');

            return new Cookie($secret);
        });

        $container->alias(Cookie::class, 'cookie');
    }
}
