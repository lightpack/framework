<?php

namespace Lightpack\Schedule;

use Lightpack\Container\Container;

class Schedule
{
    /**
     * @var array<Event>
     */
    private array $events = [];

    /**
     * Add a new job to the schedule.
     */
    public function job(string $job): Event
    {
        return $this->addEvent('job', $job);
    }

    /**
     * Returns all scheduled events.
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Returns the list of events that are due.
     */
    public function getDueEvents(): array
    {
        return array_filter($this->events, fn ($event) => $event->isDue());
    }

    /**
     * Run all scheduled events synchronously.
     */
    public function run()
    {
        foreach ($this->getDueEvents() as $event) {
            if ($event->getType() === 'job') {
                $this->dispatchJob($event);
            }
        }
    }

    /**
     * Dispatch a job to the queue.
     */
    private function dispatchJob(Event $event)
    {
        /** @var \Lightpack\Jobs\Job */
        $job = Container::getInstance()->resolve($event->getName());

        $job->dispatch();
    }

    private function addEvent(string $type, string $data): Event
    {
        $event = new Event($type, $data);

        $this->events[] = $event;

        return $event;
    }
}
