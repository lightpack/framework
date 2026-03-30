<?php

namespace Lightpack\Settings;

use Lightpack\Support\ProviderInterface;
use Lightpack\Settings\Settings;
use Lightpack\Container\Container;

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
