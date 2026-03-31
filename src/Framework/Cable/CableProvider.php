<?php

namespace Lightpack\Cable;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\Cable\Cable;
use Lightpack\Cable\CableManager;
use Lightpack\Cable\Presence;
use Lightpack\Cable\PresenceManager;

class CableProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('cable.manager', function ($container) {
            return new CableManager($container);
        });

        $container->register('cable', function ($container) {
            return $container->get('cable.manager')->driver();
        });

        $container->alias(CableManager::class, 'cable.manager');
        $container->alias(Cable::class, 'cable');
        
        // Register Presence service
        $container->register('presence.manager', function ($container) {
            return new PresenceManager($container);
        });

        $container->register('presence', function ($container) {
            return $container->get('presence.manager')->driver();
        });
        
        $container->alias(PresenceManager::class, 'presence.manager');
        $container->alias(Presence::class, 'presence');
    }
}
