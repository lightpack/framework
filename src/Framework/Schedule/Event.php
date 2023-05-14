<?php

namespace Lightpack\Schedule;

class Event
{
    private string $cronExpression;

    public function __construct(private string $type, private string $name)
    {
        // ...
    }

    public function cron(string $expression): self
    {
        $this->cronExpression = $expression;

        return $this;
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDue(): bool
    {
        $cron = new Cron($this->cronExpression);

        return $cron->isDue(new \DateTime());
    }

    /**
     * Check if the event is due at a specific date and time.
     *
     * @param \DateTime $dateTime The date and time to check the due status.
     * @return bool True if the event is due at the specified date and time, false otherwise.
     */
    public function isDueAt(\DateTime $dateTime): bool
    {
        $cron = new Cron($this->cronExpression);

        return $cron->isDue($dateTime);
    }

    public function nextDueAt(): ?\DateTime
    {
        $cron = new Cron($this->cronExpression);

        return $cron->nextDueAt(new \DateTime());
    }
}
