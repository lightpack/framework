<?php

namespace Lightpack\Settings;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class SettingsProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->factory('settings', function ($container) {
            return new Settings(
                $container->get('db'),
                $container->get('cache'),
                $container->get('config')
            );
        });

        $container->alias(Settings::class, 'settings');
    }
}
