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
}
