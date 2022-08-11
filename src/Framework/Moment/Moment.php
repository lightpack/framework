<?php

namespace Lightpack\Moment;

use DateTime;

/**
 * Simple datetime utility class.
 */
class Moment
{
    private static $format = 'Y-m-d H:i:s';

    public static function format(string $format): void
    {
        self::$format = $format;
    }

    public static function today(string $format = null): string
    {
        $datetime = new DateTime('today');

        return $datetime->format($format ?? self::$format);
    }

    public static function tomorrow(string $format = null): string
    {
        $datetime = new DateTime('tomorrow');

        return $datetime->format($format ?? self::$format);
    }

    public static function yesterday(string $format = null): string
    {
        $datetime = new DateTime('yesterday');

        return $datetime->format($format ?? self::$format);
    }

    public static function next(string $dayname, string $format = null): string
    {
        $datetime = new DateTime('next ' . strtolower($dayname));

        return $datetime->format($format ?? self::$format);
    }

    public static function last(string $dayname, string $format = null): string
    {
        $datetime = new DateTime('last ' . strtolower($dayname));

        return $datetime->format($format ?? self::$format);
    }

    public static function  thisMonthEnd(string $format = null): string
    {
        $datetime = new DateTime('last day of this month');

        return $datetime->format($format ?? self::$format);
    }

    public static function  nextMonthEnd(string $format = null): string
    {
        $datetime = new DateTime('last day of next month');

        return $datetime->format($format ?? self::$format);
    }

    public static function  lastMonthEnd(string $format = null): string
    {
        $datetime = new DateTime('last day of last month');

        return $datetime->format($format ?? self::$format);
    }

    public static function diff(string $datetime1, string $datetime2): \DateInterval
    {
        $datetimetime1 = new DateTime($datetime1);
        $datetimetime2 = new DateTime($datetime2);

        return $datetimetime1->diff($datetimetime2, true);
    }

    public static function daysBetween(string $datetime1, string $datetime2): int
    {
        return self::diff($datetime1, $datetime2)->days;
    }

    public static function now($format = null): string
    {
        $datetime = new DateTime('now');

        return $datetime->format($format ?? self::$format);
    }

    public static function create(string $datetime = 'now'): DateTime
    {
        return new DateTime($datetime);
    }

    public static function travel(string $modifier, string $format = null): string
    {
        $datetime = self::create();

        return $datetime->modify($modifier)->format($format ?? self::$format);
    }

    public static function fromNow(string $datetime = 'now'): ?string
    {
        $datetimetime1 = new DateTime();
        $datetimetime2 = new DateTime($datetime);

        // Seconds from now
        $seconds = $datetimetime1->getTimestamp() - $datetimetime2->getTimestamp();

        if ($seconds <= 60) {
            return 'just now';
        }

        // Minutes from now
        $minutes = round($seconds / 60);

        if ($minutes == 1) {
            return 'a minute ago';
        }

        if ($minutes <= 60) {
            return "{$minutes} minutes ago";
        }

        // Hours from now
        $hours = round($seconds / 3600);

        if ($hours == 1) {
            return 'an hour ago';
        }

        if ($hours <= 24) {
            return "{$hours} hours ago";
        }

        // Days from now
        $days = round($seconds / 86400);

        if ($days == 1) {
            return 'yesterday';
        }

        if ($days <= 7) {
            return "{$days} days ago";
        }

        // Weeks from now
        $weeks = round($seconds / 604800);

        if ($weeks == 1) {
            return 'a week ago';
        }

        if ($weeks <= 4.3) {
            return "{$weeks} weeks ago";
        }

        // Months from now
        $months = round($seconds / 2600640);

        if ($months == 1) {
            return 'a month ago';
        }

        if ($months <= 12) {
            return "{$months} months ago";
        }

        // Years from now
        $years = round($seconds / 31207680);

        if ($years == 1) {
            return 'a year ago';
        }

        return "{$years} years ago";
    }
}
