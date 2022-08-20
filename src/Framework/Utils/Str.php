<?php

namespace Lightpack\Utils;

class Str
{
    protected static $singularCache = [];
    protected static $pluralCache = [];

    protected static $singulars = [
        '/(quiz)zes$/i' => '$1',
        '/(matr)ices$/i' => '$1ix',
        '/(vert|ind)ices$/i' => '$1ex',
        '/^(ox)en/i' => '$1',
        '/(alias|status)es$/i' => '$1',
        '/([octop|vir])i$/i' => '$1us',
        '/(cris|ax|test)es$/i' => '$1is',
        '/(shoe)s$/i' => '$1',
        '/(o)es$/i' => '$1',
        '/(bus)es$/i' => '$1',
        '/([m|l])ice$/i' => '$1ouse',
        '/(x|ch|ss|sh)es$/i' => '$1',
        '/(m)ovies$/i' => '$1ovie',
        '/(s)eries$/i' => '$1eries',
        '/([^aeiouy]|qu)ies$/i' => '$1y',
        '/([lr])ves$/i' => '$1f',
        '/(tive)s$/i' => '$1',
        '/(hive)s$/i' => '$1',
        '/([^f])ves$/i' => '$1fe',
        '/(^analy)ses$/i' => '$1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
        '/([ti])a$/i' => '$1um',
        '/(n)ews$/i' => '$1ews',
        '/(h|bl)ouses$/i' => '$1ouse',
        '/(corpse)s$/i' => '$1',
        '/(us)es$/i' => '$1',
        '/s$/i' => '',
    ];

    protected static $plurals = [
        '/(quiz)$/i' => '$1zes',
        '/(matr|vert|ind)ix|ex$/i' => "$1ices",
        '/^(ox)$/i' => '$1en',
        '/(alias|status)$/i' => '$1es',
        '/(cris|ax|test)is$/i' => '$1es',
        '/(o)e$/i' => '$1es',
        '/([m|l])ouse$/i' => "$1ice",
        '/(x|ch|ss|sh)$/i' => '$1es',
        '/(m)ovie$/i' => '$1ovies',
        '/(s)eries$/i' => '$1eries',
        '/([^aeiouy]|qu)y$/i' => '$1ies',
        '/([lr])fe$/i' => '$1fes',
        '/(tive)$/i' => '$1s',
        '/(hive)$/i' => '$1s',
        '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
        '/(shea|lea|loa|thie)f$/i' => "$1ves",
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)sis$/i' => '$1ses',
        '/([ti])a$/i' => '$1a',
        '/(n)ews$/i' => '$1ews',
        '/(h|bl)ouse$/i' => '$1ouses',
        '/(corpse)$/i' => '$1s',
        '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
        '/(us)$/i' => '$1es',
        '/(ase)$/i' => '$1s',
        '/s$/i' => '$1s',
        '/$/' => "s"
    ];

    protected static $irregulars = [
        'move' => 'moves',
        'foot' => 'feet',
        'goose' => 'geese',
        'child' => 'children',
        'tooth' => 'teeth',
        'person' => 'people',
        'man' => 'men',
        'zoo' => 'zoos',
        'sex' => 'sexes',
    ];

    protected static $uncountables = [
        'equipment',
        'information',
        'rice',
        'money',
        'species',
        'series',
        'fish',
        'sheep',
        'deer',
        'aircraft',
        'data',
    ];

    public static function singularize(string $subject): string
    {
        if (self::$singularCache[$subject] ?? null) {
            return self::$singularCache[$subject];
        }

        if (in_array($subject, static::$uncountables)) {
            return self::$singularCache[$subject] = $subject;
        }

        if (in_array($subject, static::$irregulars)) {
            return self::$singularCache[$subject] = array_search($subject, static::$irregulars);
        }

        foreach (static::$singulars as $pattern => $result) {
            if (preg_match($pattern, $subject)) {
                return self::$singularCache[$subject] = preg_replace($pattern, $result, $subject);
            }
        }

        return $subject;
    }

    public static function pluralize(string $subject): string
    {
        if (self::$pluralCache[$subject] ?? null) {
            return self::$pluralCache[$subject];
        }

        if (in_array($subject, static::$uncountables)) {
            return self::$pluralCache[$subject] = $subject;
        }

        if (array_key_exists($subject, static::$irregulars)) {
            return self::$pluralCache[$subject] = static::$irregulars[$subject];
        }

        foreach (static::$plurals as $pattern => $result) {
            if (preg_match($pattern, $subject)) {
                return self::$pluralCache[$subject] = preg_replace($pattern, $result, $subject);
            }
        }

        return $subject;
    }

    public static function pluralizeIf(int $number, string $subject): string
    {
        if ($number == 1) {
            return $subject;
        }

        return static::pluralize($subject);
    }

    public static function camelize(string $subject): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $subject)));
    }

    public static function variable(string $subject): string
    {
        return lcfirst(static::camelize($subject));
    }

    public static function underscore(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $subject));

        return str_replace([' ', '-'], '_', $subject);
    }

    public static function dasherize(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $subject));

        return str_replace([' ', '_'], '-', $subject);
    }

    public static function humanize(string $subject): string
    {
        return ucwords(str_replace('_', ' ', $subject));
    }

    public static function tableize(string $subject): string
    {
        return static::pluralize(static::underscore($subject));
    }

    public static function classify(string $subject): string
    {
        return static::camelize(static::singularize($subject));
    }

    public static function ordinalize(int $number): string
    {
        if (in_array(($number % 100), range(11, 13))) {
            return $number . 'th';
        }

        switch (($number % 10)) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }

    public static function slugify(string $subject, string $separator = '-'): string
    {
        $subject = preg_replace('/[^a-zA-Z0-9]/', ' ', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        $subject = trim($subject);
        $subject = str_replace(' ', $separator, $subject);

        return strtolower($subject);
    }

    public static function startsWith(string $subject, string $prefix): bool
    {
        return substr($subject, 0, strlen($prefix)) == $prefix;
    }

    public static function endsWith(string $subject, string $suffix): bool
    {
        return substr($subject, -strlen($suffix)) == $suffix;
    }

    public static function contains(string $subject, string $needle): bool
    {
        return strpos($subject, $needle) !== false;
    }

    public static function random(int $length = 16): string
    {
        if ($length < 2) {
            return '';
        }

        return bin2hex(random_bytes($length / 2));
    }

    public static function mask(string $subject, string $mask = '*', int $start = 0): string
    {
        $length = strlen($subject);
        $masked = substr($subject, 0, $start);

        for ($i = $start; $i < $length; $i++) {
            $masked .= $mask;
        }

        return $masked;
    }
}
