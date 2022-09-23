<?php

namespace Lightpack\Event;

use Lightpack\Container\Container;

class Event
{
    protected $subscribers = [];

    public function __construct(private Container $container) {}

    public function subscribe(string $eventName, string $eventSubscriber): void
    {
        $this->subscribers[$eventName][] = $eventSubscriber;
    }

    public function unsubscribe(string $eventSubscriber): void
    {
        $eventNames = array_keys($this->subscribers);

        foreach ($eventNames as $eventName) {
            $key = array_search($eventSubscriber, $this->subscribers[$eventName]);

            if ($key !== false) {
                unset($this->subscribers[$eventName][$key]);
            }
        }
    }

    public function fire(string $event, mixed $data = null): void
    {
        $this->throwExceptionIfEventNotFound($event);
        
        foreach ($this->subscribers[$event] as $subscriber) {
            $this->container->call($subscriber, 'handler', $data);
        }
    }

    public function getSubscribers()
    {
        return $this->subscribers;
    }

    protected function throwExceptionIfEventNotFound(string $eventName): void
    {
        if (!isset($this->subscribers[$eventName])) {
            throw new \Lightpack\Exceptions\EventNotFoundException(
                sprintf(
                    'Event `%s` is not registered',
                    $eventName
                )
            );
        }
    }
}
