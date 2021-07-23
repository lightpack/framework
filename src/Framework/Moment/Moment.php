<?php

namespace Lightpack\Moment;

/**
 * Simple datetime utility class.
 */
class Moment
{
    public static function today(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('today');

        return $date->format($format);
    }

    public static function tomorrow(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('tomorrow');

        return $date->format($format);
    }

    public static function yesterday(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('yesterday');

        return $date->format($format);
    }

    public static function this(string $day, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('this ' . strtolower($day));

        return $date->format($format);
    }

    public static function next(string $day, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('next ' . strtolower($day));

        return $date->format($format);
    }

    public static function last(string $day, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('last ' . strtolower($day));

        return $date->format($format);
    }

    public static function previous(string $day, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('previous ' . strtolower($day));

        return $date->format($format);
    }

    public static function ago(int $days, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime("-{$days} days");

        return $date->format($format);
    }

    public static function after(int $days, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime("+{$days} days");

        return $date->format($format);
    }

    public static function  endOfThisMonth(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('last day of this month');

        return $date->format($format);
    }

    public static function  endOfNextMonth(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('last day of next month');

        return $date->format($format);
    }

    public static function  endOfPreviousMonth(string $format = 'Y-m-d'): string
    {
        $date = new \DateTime('last day of previous month');

        return $date->format($format);
    }

    public static function add(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime($date);

        return $date->modify("+$days days")->format($format);
    }

    public static function remove(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $date = new \DateTime($date);

        return $date->modify("-$days days")->format($format);
    }

    public static function diff(string $date1, string $date2): \DateInterval
    {
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);

        return $datetime1->diff($datetime2, true);
    }

    public static function daysBetween(string $date1, string $date2): int
    {
        return self::diff($date1, $date2)->days;
    }

    public static function fromNow(string $date): ?string
    {
        $datetime1 = new \DateTime();
        $datetime2 = new \DateTime($date);

        // Seconds
        $seconds = $datetime1->getTimestamp() - $datetime2->getTimestamp();

        if($seconds <= 60) {
            return 'just now';
        }

        // Minutes
        $minutes = round($seconds / 60);
        
        if($minutes == 1) {
            return 'a minute ago';
        }

        if($minutes <= 60) {
            return "{$minutes} minutes ago";
        }

        // Hours
        $hours = round($seconds / 3600);

        if($hours == 1) {
            return 'an hour ago';
        }

        if($hours <= 24) {
            return "{$hours} hours ago";
        }

        // Days
        $days = round($seconds / 86400);

        if($days == 1) {
            return 'yesterday';
        }

        if($days <= 7) {
            return "{$days} days ago";
        }

        // Weeks
        $weeks = round($seconds / 604800);

        if($weeks == 1) {
            return 'a week ago';
        }

        if($weeks <= 4.3) {
            return "{$weeks} weeks ago";
        }
        
        // Months
        $months = round($seconds / 2600640);

        if($months == 1) {
            return 'a month ago';
        }

        if($months <= 12) {
            return "{$months} months ago";
        }

        // Years
        $years = round($seconds / 31207680);

        if($years == 1) {
            return 'a year ago';
        }
        
        return "{$years} years ago";
    }
}
