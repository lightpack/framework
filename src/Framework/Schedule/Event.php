<?php

namespace Lightpack\Schedule;

class Event
{
    private string $cronExpression;
    private Cron $cron;

    public function __construct(private string $type, private string $name)
    {
        $this->cronExpression = '* * * * *';
        $this->cron = new Cron($this->cronExpression);
    }

    /**
     * Set a new cron instance useful for setting mock Cron instance while testing.
     */
    public function setCronInstance(Cron $cron) 
    {
        $this->cron = $cron;
    }

    public function cron(string $expression): self
    {
        $this->cronExpression = $expression;
        $this->cron = new Cron($this->cronExpression);

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
        return $this->cron->isDue(new \DateTime());
    }

    /**
     * Check if the event is due at a specific date and time.
     *
     * @param \DateTime $dateTime The date and time to check the due status.
     * @return bool True if the event is due at the specified date and time, false otherwise.
     */
    public function isDueAt(\DateTime $dateTime): bool
    {
        return $this->cron->isDue($dateTime);
    }

    public function nextDueAt(): ?\DateTime
    {
        $cron = new Cron($this->cronExpression);

        return $this->cron->nextDueAt(new \DateTime());
    }

    public function previousDueAt(): ?\DateTime
    {
        return $this->cron->previousDueAt(new \DateTime());
    }
}
