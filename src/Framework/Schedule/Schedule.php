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
     * Add a new command to the schedule.
     */
    public function command(string $command, array $arguments = []): Event
    {
        return $this->addCommand($command, $arguments);
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
            } elseif ($event->getType() === 'command') {
                $this->executeCommand($event);
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

        $job->dispatch($event->getData());
    }

    /**
     * Execute a console command.
     */
    private function executeCommand(Event $event)
    {
        $command = Container::getInstance()->resolve($event->getName());
        $command->run($event->getData());
    }

    /**
     * Add a command event to the schedule.
     */
    private function addCommand(string $command, array $arguments): Event
    {
        $event = new Event('command', $command, $arguments);
        $this->events[] = $event;
        return $event;
    }

    /**
     * Add an event to the schedule.
     */
    private function addEvent(string $type, string $data): Event
    {
        $event = new Event($type, $data);

        $this->events[] = $event;

        return $event;
    }
}
