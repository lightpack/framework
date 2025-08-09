<?php

namespace Lightpack\Providers;

use Lightpack\Http\Cookie;
use Lightpack\Container\Container;

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
