<?php

namespace Lightpack\Schedule;

class Event
{
    private string $cronExpression;
    private Cron $cron;

    public function __construct(private string $type, private string $name, private array $data = [])
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

    public function getData(): array
    {
        return $this->data;
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

    /**
     * Set the event to run every day at midnight.
     */
    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Set the event to run every hour at minute 0.
     */
    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Set the event to run every week on Sunday at midnight.
     */
    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Set the event to run every month on the 1st at midnight.
     */
    public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Set the event to run every N minutes.
     */
    public function everyMinutes(int $minutes): self
    {
        return $this->cron("*/{$minutes} * * * *");
    }

    /**
     * Set the event to run daily at a specific time (HH:MM).
     */
    public function dailyAt(string $time): self
    {
        [$hour, $minute] = explode(':', $time);
        // Cast to int to remove leading zeros
        return $this->cron(((int)$minute) . ' ' . ((int)$hour) . ' * * *');
    }

    /**
     * Set the event to run on Monday at midnight.
     */
    public function mondays(): self
    {
        return $this->cron('0 0 * * 1');
    }
    /**
     * Set the event to run on Tuesday at midnight.
     */
    public function tuesdays(): self
    {
        return $this->cron('0 0 * * 2');
    }
    /**
     * Set the event to run on Wednesday at midnight.
     */
    public function wednesdays(): self
    {
        return $this->cron('0 0 * * 3');
    }
    /**
     * Set the event to run on Thursday at midnight.
     */
    public function thursdays(): self
    {
        return $this->cron('0 0 * * 4');
    }
    /**
     * Set the event to run on Friday at midnight.
     */
    public function fridays(): self
    {
        return $this->cron('0 0 * * 5');
    }
    /**
     * Set the event to run on Saturday at midnight.
     */
    public function saturdays(): self
    {
        return $this->cron('0 0 * * 6');
    }
    /**
     * Set the event to run on Sunday at midnight.
     */
    public function sundays(): self
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Set the event to run monthly on a specific day and time (default 00:00).
     */
    public function monthlyOn(int $day, string $time = '00:00'): self
    {
        [$hour, $minute] = explode(':', $time);
        // Cast to int to remove leading zeros
        return $this->cron(((int)$minute) . ' ' . ((int)$hour) . " {$day} * *");
    }
}
