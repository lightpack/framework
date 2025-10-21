<?php

namespace Lightpack\Providers;

use Lightpack\Config\Config;
use Lightpack\Container\Container;

class ConfigProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('config', function ($container) {
            // Pass DIR_CONFIG if defined, otherwise Config will throw an exception
            $configDir = defined('DIR_CONFIG') ? \DIR_CONFIG : null;
            return new Config($configDir);
        });

        $container->alias(Config::class, 'config');
    }
}
