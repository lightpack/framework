<?php

namespace Lightpack\Event;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

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
