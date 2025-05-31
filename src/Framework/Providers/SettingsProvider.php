<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Settings\Settings;

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
