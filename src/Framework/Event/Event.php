<?php

namespace Lightpack\Event;

use Lightpack\Container\Container;
use Lightpack\Exceptions\EventHandlerMethodNotFoundException;
use Lightpack\Exceptions\EventNotFoundException;

class Event
{
    protected $subscribers = [];

    public function __construct(protected Container $container)
    {
    }

    public function subscribe(string $event, string $subscriber): void
    {
        $this->subscribers[$event][] = $subscriber;
    }

    public function unsubscribe(string $subscriber): void
    {
        $events = array_keys($this->subscribers);

        foreach ($events as $event) {
            $key = array_search($subscriber, $this->subscribers[$event]);

            if ($key !== false) {
                unset($this->subscribers[$event][$key]);
            }
        }
    }

    public function fire(string $event, mixed $data = null): void
    {
        $this->throwExceptionIfEventNotFound($event);

        foreach ($this->subscribers[$event] as $subscriber) {
            $subscriberInstance = $this->container->resolve($subscriber);

            $this->throwExceptionIfHandleMethodNotFound($subscriberInstance);

            $subscriberInstance->handle($data);
        }
    }

    public function getSubscribers()
    {
        return $this->subscribers;
    }

    protected function throwExceptionIfEventNotFound(string $event): void
    {
        if (!isset($this->subscribers[$event])) {
            throw new EventNotFoundException(
                sprintf(
                    'Event `%s` is not registered',
                    $event
                )
            );
        }
    }

    public function throwExceptionIfHandleMethodNotFound(object $subscriber)
    {
        if (!method_exists($subscriber, 'handle')) {
            throw new EventHandlerMethodNotFoundException(sprintf('The handle() method is not defined in event class `%s`', get_class($subscriber)));
        }
    }
}
