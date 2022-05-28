<?php

namespace Lightpack\Utils;

class Arr
{
    /**
     * Check if an array has key using 'dot' notation.
     * 
     * For example:
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * Arr::hasKey('a.b.c', $array) === true;
     */

    public static function has(string $key, array $array): bool
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return false;
            }

            $array = $array[$key];
        }

        return isset($array[array_shift($keys)]);
    }

    /**
     * Get value from array using 'dot' notation.
     * 
     * For example:
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * Arr::get('a.b.c', $array) === 'd';
     */
    public static function get(string $key, array $array, $default = null)
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $default;
            }

            $array = $array[$key];
        }

        return $array[array_shift($keys)] ?? $default;
    }
}
