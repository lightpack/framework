<?php

namespace Lightpack\Schedule;

class Cron
{
    /** @var string */
    protected $minutes;

    /** @var string */
    protected $hours;

    /** @var string */
    protected $days;

    /** @var string */
    protected $months;

    /** @var string */
    protected $weekdays;

    /**
     * @var string $cronTime Cron time expression.
     * Example: '* * * * *'
     */
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

    public function minuteIsDue()
    {
        return $this->checkIfDue($this->minutes, date('i'));
    }

    public function hourIsDue()
    {
        return $this->checkIfDue($this->hours, date('H'));
    }

    public function dayIsDue()
    {
        return $this->checkIfDue($this->days, date('j'));
    }

    public function monthIsDue()
    {
        return $this->checkIfDue($this->months, date('n'));
    }

    public function weekdayIsDue()
    {
        return $this->checkIfDue($this->weekdays, date('w'));
    }

    public static function isDue(string $cronExpression)
    {
        $cron = new self($cronExpression);

        return $cron->minuteIsDue()
            && $cron->hourIsDue()
            && $cron->dayIsDue()
            && $cron->monthIsDue()
            && $cron->weekdayIsDue();
    }

    protected function checkIfDue($expression, $current)
    {
        if ($expression === '*') {
            return true;
        }

        if ($expression === $current) {
            return true;
        }

        if (strpos($expression, '-') !== false) {
            $parts = explode('-', $expression);
            if ($current >= $parts[0] && $current <= $parts[1]) {
                return true;
            }
        }

        if (strpos($expression, '/') !== false) {
            $parts = explode('/', $expression);
            if ($current % $parts[1] === 0) {
                return true;
            }
        }

        if (strpos($expression, ',') !== false) {
            $parts = explode(',', $expression);
            foreach ($parts as $part) {
                if ($part === $current) {
                    return true;
                }
            }
        }

        return false;
    }
}
