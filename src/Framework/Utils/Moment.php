<?php

namespace Lightpack\Utils;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Simple datetime utility class.
 *
 * This class handles datetime operations with proper timezone support.
 * All internal operations are done in UTC and can be converted to any timezone
 * for display purposes.
 */
class Moment
{
    /**
     * @var string
     */
    private $format = 'Y-m-d H:i:s';

    /**
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * Initialize Moment with a specific timezone.
     * Defaults to UTC if no timezone is provided.
     */
    public function __construct(?string $timezone = 'UTC')
    {
        try {
            $this->timezone = new DateTimeZone($timezone);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid timezone: {$timezone}");
        }
    }

    /**
     * Set the output format for dates
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Change the timezone for output
     */
    public function setTimezone(string $timezone): self
    {
        try {
            $this->timezone = new DateTimeZone($timezone);

            return $this;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid timezone: {$timezone}");
        }
    }

    /**
     * Get current timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone->getName();
    }

    /**
     * Create a new DateTime object with the current timezone
     */
    public function create(string $datetime = 'now'): DateTime
    {
        try {
            return new DateTime($datetime, $this->timezone);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid datetime format: {$datetime}");
        }
    }

    public function today(?string $format = null): string
    {
        return $this->create('today')->format($format ?? $this->format);
    }

    public function tomorrow(?string $format = null): string
    {
        return $this->create('tomorrow')->format($format ?? $this->format);
    }

    public function yesterday(?string $format = null): string
    {
        return $this->create('yesterday')->format($format ?? $this->format);
    }

    public function next(string $dayname, ?string $format = null): string
    {
        return $this->create('next ' . strtolower($dayname))->format($format ?? $this->format);
    }

    public function last(string $dayname, ?string $format = null): string
    {
        return $this->create('last ' . strtolower($dayname))->format($format ?? $this->format);
    }

    public function thisMonthEnd(?string $format = null): string
    {
        return $this->create('last day of this month')->format($format ?? $this->format);
    }

    public function nextMonthEnd(?string $format = null): string
    {
        return $this->create('last day of next month')->format($format ?? $this->format);
    }

    public function lastMonthEnd(?string $format = null): string
    {
        return $this->create('last day of last month')->format($format ?? $this->format);
    }

    public function diff(DateTime|string $datetime1, DateTime|string $datetime2): \DateInterval
    {
        try {
            $date1 = $this->resolve($datetime1);
            $date2 = $this->resolve($datetime2);

            return $date1->diff($date2, true);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid datetime format provided");
        }
    }

    public function daysBetween(DateTime|string $datetime1, DateTime|string $datetime2): int
    {
        return $this->diff($datetime1, $datetime2)->days;
    }

    public function humanDiff(DateTime|string $from, DateTime|string $to = 'now'): string
    {
        $date1 = $this->resolve($from);
        $date2 = $this->resolve($to);
        $isPast = $date1->getTimestamp() <= $date2->getTimestamp();
        $seconds = abs($date2->getTimestamp() - $date1->getTimestamp());

        if ($seconds <= 60) {
            return $isPast ? 'just now' : 'in a moment';
        }

        $minutes = round($seconds / 60);
        if ($minutes == 1) {
            return $isPast ? 'a minute ago' : 'in a minute';
        }
        if ($minutes <= 60) {
            return $isPast ? "{$minutes} minutes ago" : "in {$minutes} minutes";
        }

        $hours = round($seconds / 3600);
        if ($hours == 1) {
            return $isPast ? 'an hour ago' : 'in an hour';
        }
        if ($hours < 24) {
            return $isPast ? "{$hours} hours ago" : "in {$hours} hours";
        }

        $days = round($seconds / 86400);
        if ($days == 1) {
            return $isPast ? 'yesterday' : 'tomorrow';
        }
        if ($days < 7) {
            return $isPast ? "{$days} days ago" : "in {$days} days";
        }

        $weeks = round($seconds / 604800);
        if ($weeks == 1) {
            return $isPast ? 'a week ago' : 'in a week';
        }
        if ($weeks <= 4.3) {
            return $isPast ? "{$weeks} weeks ago" : "in {$weeks} weeks";
        }

        $months = round($seconds / 2629743.83);
        if ($months == 1) {
            return $isPast ? 'a month ago' : 'in a month';
        }
        if ($months <= 12) {
            return $isPast ? "{$months} months ago" : "in {$months} months";
        }

        $years = round($seconds / 31556926);
        if ($years == 1) {
            return $isPast ? 'a year ago' : 'in a year';
        }

        return $isPast ? "{$years} years ago" : "in {$years} years";
    }

    public function now(?string $format = null): string
    {
        return $this->create('now')->format($format ?? $this->format);
    }

    public function travel(string $modifier, ?string $format = null): string
    {
        try {
            $datetime = $this->create();

            return $datetime->modify($modifier)->format($format ?? $this->format);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid modifier: {$modifier}");
        }
    }

    public function fromNow(DateTime|string $datetime = 'now'): string
    {
        try {
            $current = $this->create('now');
            $target = $this->resolve($datetime);

            $seconds = $current->getTimestamp() - $target->getTimestamp();

            if ($seconds <= 60) {
                return 'just now';
            }

            $minutes = round($seconds / 60);
            if ($minutes == 1) {
                return 'a minute ago';
            }
            if ($minutes <= 60) {
                return "{$minutes} minutes ago";
            }

            $hours = round($seconds / 3600);
            if ($hours == 1) {
                return 'an hour ago';
            }
            if ($hours < 24) {
                return "{$hours} hours ago";
            }

            $days = round($seconds / 86400);
            if ($days == 1) {
                return 'yesterday';
            }
            if ($days < 7) {
                return "{$days} days ago";
            }

            $weeks = round($seconds / 604800);
            if ($weeks == 1) {
                return 'a week ago';
            }
            if ($weeks <= 4.3) {
                return "{$weeks} weeks ago";
            }

            $months = round($seconds / 2629743.83); // Average month in seconds
            if ($months == 1) {
                return 'a month ago';
            }
            if ($months <= 12) {
                return "{$months} months ago";
            }

            $years = round($seconds / 31556926); // Average year in seconds
            if ($years == 1) {
                return 'a year ago';
            }

            return "{$years} years ago";
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid datetime format: {$datetime}");
        }
    }

    public function isToday(DateTime|string $datetime = 'now'): bool
    {
        $date = $this->resolve($datetime);
        $today = $this->create('today');

        return $date->format('Y-m-d') === $today->format('Y-m-d');
    }

    public function isPast(DateTime|string $datetime = 'now'): bool
    {
        return $this->resolve($datetime)->getTimestamp() < $this->create('now')->getTimestamp();
    }

    public function isFuture(DateTime|string $datetime = 'now'): bool
    {
        return $this->resolve($datetime)->getTimestamp() > $this->create('now')->getTimestamp();
    }

    public function startOfDay(DateTime|string $datetime = 'now', ?string $format = null): string
    {
        $date = $this->resolve($datetime);
        $date->setTime(0, 0, 0);

        return $date->format($format ?? $this->format);
    }

    public function endOfDay(DateTime|string $datetime = 'now', ?string $format = null): string
    {
        $date = $this->resolve($datetime);
        $date->setTime(23, 59, 59);

        return $date->format($format ?? $this->format);
    }

    public function age(DateTime|string $birthdate): int
    {
        $birth = $this->resolve($birthdate);
        $now = $this->create('now');

        return $birth->diff($now)->y;
    }

    /**
     * Resolve a DateTime or string into a DateTime object in Moment's timezone.
     */
    private function resolve(DateTime|string $datetime): DateTime
    {
        if ($datetime instanceof DateTime) {
            $clone = clone $datetime;
            $clone->setTimezone($this->timezone);

            return $clone;
        }

        return $this->create($datetime);
    }
}
