<?php

namespace Lightpack\Utils;

use DateTime;
use ValueError;
use JsonException;

class Arr
{
    /**
     * Check if an array has key using 'dot' notation.
     * 
     * For example:
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * (new Arr)->hasKey('a.b.c', $array) === true;
     */

    public function has(string $key, array $array): bool
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
     * ```php
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * (new Arr)->get('a.b.c', $array) === 'd';
     * ```
     */
    public function get(string $key, array $data, $default = null)
    {
        $keys = explode('.', $key);
        $result = $data;

        while (count($keys) > 0) {
            $segment = array_shift($keys);

            if ($segment === '*') {
                if (!is_array($result)) {
                    return $default;
                }

                $items = [];
                $remainingPath = implode('.', $keys);
                
                foreach ($result as $item) {
                    if ($remainingPath !== '') {
                        if (is_array($item)) {
                            $value = $this->get($remainingPath, $item, null);
                            if ($value !== null) {
                                $items[] = $value;
                            }
                        }
                    } else {
                        $items[] = $item;
                    }
                }

                return empty($items) ? $default : $items;
            }

            // Handle array access (numeric keys)
            if (is_numeric($segment)) {
                $segment = (int) $segment;
            }

            if (!isset($result[$segment])) {
                return $default;
            }

            $result = $result[$segment];
        }

        return $result;
    }

    /**
     * Set array value using dot notation.
     * 
     * For example:
     * $array = ['a' => ['b' => []]];
     * (new Arr)->set('a.b.c', 'value', $array);
     * // $array is now ['a' => ['b' => ['c' => 'value']]]
     * 
     * @throws ValueError When key is empty
     */
    public function set(string $key, $value, array &$array): array
    {
        if (empty($key)) {
            throw new ValueError('Key cannot be empty');
        }

        $keys = explode('.', $key);
        $current = &$array;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Remove one or more array items from a given array using "dot" notation.
     *
     * @param array $array
     * @param array|string $keys
     * @return void
     */
    public function delete(string $key, array &$array): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        while(count($keys) > 1) {
            $key = array_shift($keys);
            
            if(!isset($current[$key]) || !is_array($current[$key])) {
                return;
            }
            
            $current = &$current[$key];
        }
        
        unset($current[array_shift($keys)]);
    }

        /**
     * Build a tree from a flat array. 
     * 
     * The tree will contain a 'children' key for each element in the 
     * array. Each child will be grouped by the value of the parent key.
     * 
     * @param array $array The array to build the tree from.
     * @param mixed $parentId The value to use for the parent ID.
     * @param string $idKey The key name to use for the ID.
     * @param string $parentIdKey The key name to use for the parent ID.
     * 
     * @return array The tree.
     * 
     * Example:
     * ```php
     * $categories = [
     *    ['id' => 1, 'parent_id' => null, 'name' => 'Category 1'],
     *    ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
     *    ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
     *    ['id' => 4, 'parent_id' => 2, 'name' => 'Category 4'],
     *    ['id' => 5, 'parent_id' => null, 'name' => 'Category 5'],
     * ];
     * 
     * $tree = (new Arr)->tree($categories);
     * ```
     */
    public function tree(array $items, $parentId = null, string $idKey = 'id', string $parentIdKey = 'parent_id'): array
    {
        $result = [];

        foreach ($items as $key => $item) {
            if ($item[$parentIdKey] == $parentId) {
                $result[$key] = $item;
                $result[$key]['children'] = $this->tree($items, $item[$idKey], $idKey, $parentIdKey);
            }
        }

        return $result;
    }
}
