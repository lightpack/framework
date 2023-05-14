<?php

namespace Lightpack\Schedule;

class Cron
{
    protected string $minutes;
    protected string $hours;
    protected string $days;
    protected string $months;
    protected string $weekdays;

    public function __construct(string $cronExpression)
    {
        $cronParts = explode(' ', $cronExpression);

        if (count($cronParts) !== 5) {
            throw new \Exception('Invalid cron time expression');
        }

        $this->minutes = $cronParts[0];
        $this->hours = $cronParts[1];
        $this->days = $cronParts[2];
        $this->months = $cronParts[3];
        $this->weekdays = $cronParts[4];
    }

    public function minuteIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->minutes, (int) $currentDateTime->format('i'));
    }

    public function hourIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->hours, (int) $currentDateTime->format('H'));
    }

    public function dayIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->days, (int) $currentDateTime->format('j'));
    }

    public function monthIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->months, (int) $currentDateTime->format('n'));
    }

    public function weekdayIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->weekdays, (int)$currentDateTime->format('w'));
    }

    public function isDue(\DateTime $currentDateTime): bool
    {
        return $this->minuteIsDue($currentDateTime)
            && $this->hourIsDue($currentDateTime)
            && $this->dayIsDue($currentDateTime)
            && $this->monthIsDue($currentDateTime)
            && $this->weekdayIsDue($currentDateTime);
    }

    public function nextDueAt(\DateTime $currentDateTime): \DateTime
    {
        $interval = '+1 minute';
        $dueDateTime = clone $currentDateTime;
        $dueDateTime->modify($interval);

        $maxIterations = 1440; // Maximum number of minutes in a day

        while ($maxIterations > 0) {
            if ($this->isDue($dueDateTime)) {
                return $dueDateTime;
            }

            $dueDateTime->modify($interval);
            $maxIterations--;
        }

        throw new \Exception('Unable to determine the due date within a reasonable number of iterations');
    }

    public function previousDueAt(\DateTime $currentDateTime): \DateTime
    {
        $previousDateTime = clone $currentDateTime;
        $previousDateTime->modify('-1 minute'); // Start from the current date/time

        while (true) {
            if (
                $this->minuteIsDue($previousDateTime) &&
                $this->hourIsDue($previousDateTime) &&
                $this->dayIsDue($previousDateTime) &&
                $this->monthIsDue($previousDateTime) &&
                $this->weekdayIsDue($previousDateTime)
            ) {
                return $previousDateTime;
            }

            $previousDateTime->modify('-1 minute');
        }
    }

    protected function checkIfDue($expression, $current)
    {
        if ($expression === '*') {
            return true;
        }

        if ($expression == $current) {
            return true;
        }

        if (strpos($expression, '-') !== false) {
            [$start, $end] = explode('-', $expression);
            if ((int)$current >= (int)$start && (int)$current <= (int)$end) {
                return true;
            }
        }

        if (strpos($expression, '/') !== false) {
            $parts = explode('/', $expression);
            if ((int)$current % (int)$parts[1] === 0) {
                return true;
            }
        }

        if (strpos($expression, ',') !== false) {
            $parts = explode(',', $expression);
            foreach ($parts as $part) {
                if ((int)$part === (int)$current) {
                    return true;
                }
            }
        }

        return false;
    }
}
