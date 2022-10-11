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
        return Cron::isDue($this->cronExpression);
    }
}
