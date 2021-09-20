<?php

namespace Lightpack\Providers;

use Lightpack\Config\Config;
use Lightpack\Container\Container;

class ConfigProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('config', function ($container) {
            return new Config();
        });
    }
}
