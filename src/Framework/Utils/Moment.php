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

    public function diff(string $datetime1, string $datetime2): \DateInterval
    {
        try {
            $date1 = $this->create($datetime1);
            $date2 = $this->create($datetime2);
            return $date1->diff($date2, true);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid datetime format provided");
        }
    }

    public function daysBetween(string $datetime1, string $datetime2): int
    {
        return $this->diff($datetime1, $datetime2)->days;
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

    public function fromNow(string $datetime = 'now'): ?string
    {
        try {
            $current = $this->create('now');
            $target = $this->create($datetime);

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
            if ($hours <= 24) {
                return "{$hours} hours ago";
            }

            $days = round($seconds / 86400);
            if ($days == 1) {
                return 'yesterday';
            }
            if ($days <= 7) {
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
}
