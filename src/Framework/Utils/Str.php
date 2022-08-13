<?php

namespace Lightpack\Utils;

class Str
{
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

    public static function singularize($string)
    {
        if (in_array(strtolower($string), static::$uncountables)) {
            return $string;
        }

        foreach (static::$irregulars as $result => $pattern) {
            $pattern = '/' . $pattern . '$/i';
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$singulars as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }
    
    public static function pluralize(string $subject): string
    {
        if (in_array(strtolower($subject), static::$uncountables)) {
            return $subject;
        }

        foreach (static::$irregulars as $pattern => $result) {
            $pattern = '/' . $pattern . '$/i';
            if (preg_match($pattern, $subject)) {
                return preg_replace($pattern, $result, $subject);
            }
        }

        foreach (static::$plurals as $pattern => $result) {
            if (preg_match($pattern, $subject)) {
                return preg_replace($pattern, $result, $subject);
            }
        }

        return $subject;
    }

    public static function pluralizeIf($number, $string)
    {
        if ($number == 1) {
            return $string;
        }

        return static::pluralize($string);
    }

    public static function camelize($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    public static function variable($string)
    {
        return lcfirst(static::camelize($string));
    }

    public static function underscore($string)
    {
        $string = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));

        return str_replace([' ', '-'], '_', $string);
    }

    public static function dasherize($string)
    {
        $string = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));

        return str_replace([' ', '_'], '-', $string);
    }

    public static function humanize($string)
    {
        return ucwords(str_replace('_', ' ', $string));
    }

    public static function tableize($string)
    {
        return static::pluralize(static::underscore($string));
    }

    public static function classify($string)
    {
        return static::camelize(static::singularize($string));
    }

    public static function ordinalize($number)
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

    public static function slugify($string, $separator = '-')
    {
        $string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);
        $string = str_replace(' ', $separator, $string);

        return strtolower($string);
    }

    public static function startsWith($string, $prefix)
    {
        return substr($string, 0, strlen($prefix)) == $prefix;
    }

    public static function endsWith($string, $suffix)
    {
        return substr($string, -strlen($suffix)) == $suffix;
    }

    public static function contains($string, $needle)
    {
        return strpos($string, $needle) !== false;
    }

    public static function random($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function randomAlpha($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function mask($string, $mask = '*', $start = 0)
    {
        $length = strlen($string);
        $masked = substr($string, 0, $start);

        for ($i = $start; $i < $length; $i++) {
            $masked .= $mask;
        }

        return $masked;
    }
}
