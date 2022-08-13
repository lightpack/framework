<?php

namespace Lightpack\Utils;

class Inflector
{
    protected static $singulars = [
        '/(quiz)zes$/i',
        '/(matr)ices$/i',
        '/(vert|ind)ices$/i',
        '/^(ox)en/i',
        '/(alias|status)es$/i',
        '/([octop|vir])i$/i',
        '/(cris|ax|test)es$/i',
        '/(shoe)s$/i',
        '/(o)es$/i',
        '/(bus)es$/i',
        '/([m|l])ice$/i',
        '/(x|ch|ss|sh)es$/i',
        '/(m)ovies$/i',
        '/(s)eries$/i',
        '/([^aeiouy]|qu)ies$/i',
        '/([lr])ves$/i',
        '/(tive)s$/i',
        '/(hive)s$/i',
        '/([^f])ves$/i',
        '/(^analy)ses$/i',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i',
        '/([ti])a$/i',
        '/(n)ews$/i',
        '/(h|bl)ouses$/i',
        '/(corpse)s$/i',
        '/(us)es$/i',
        '/s$/i',
    ];

    protected static $plurals = [
        '/(quiz)$/i',
        '/(matr)ix$/i',
        '/(vert|ind)ex$/i',
        '/^(ox)$/i',
        '/(alias|status)$/i',
        '/([octop|vir])$/i',
        '/(cris|ax|test)is$/i',
        '/(shoe)s$/i',
        '/(o)es$/i',
        '/(bus)es$/i',
        '/([m|l])ice$/i',
        '/(x|ch|ss|sh)es$/i',
        '/(m)ovie$/i',
        '/(s)eries$/i',
        '/([^aeiouy]|qu)y$/i',
        '/([lr])fe$/i',
        '/(tive)$/i',
        '/(hive)$/i',
        '/([^f])ve$/i',
        '/([^aeiouy]|qu)ies$/i',
        '/([lr])ves$/i',
        '/(tive)s$/i',
        '/(hive)s$/i',
        '/([^f])ves$/i',
        '/(^analy)ses$/i',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i',
        '/([ti])a$/i',
        '/(n)ews$/i',
        '/(h|bl)ouses$/i',
        '/(corpse)s$/i',
        '/(us)es$/i',
        '/s$/i',
    ];

    protected static $irregulars = [
        'move' => 'moves',
        'foot' => 'feet',
        'goose' => 'geese',
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

    public static function pluralize($string)
    {
        if (in_array(strtolower($string), static::$uncountables)) {
            return $string;
        }

        foreach (static::$irregulars as $pattern => $result) {
            $pattern = '/' . $pattern . '$/i';

            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$plurals as $pattern) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, '$1' . $result, $string);
            }
        }

        return $string;
    }

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
        foreach (static::$singulars as $pattern) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, '$1', $string);
            }
        }
        return $string;
    }

    public static function camelize($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    public static function underscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
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

    public static function pluralizeIf($number, $string)
    {
        if ($number == 1) {
            return $string;
        }
        return static::pluralize($string);
    }
}
