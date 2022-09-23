<?php

namespace Lightpack\Providers;

use Lightpack\Event\Event;
use Lightpack\Container\Container;

class EventProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('event', function ($container) {
            return new Event($container);
        });

        $container->alias(Event::class, 'event');
    }
}
