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

    /**
     * Flattens a multi-dimensional array into a single dimension.
     */
    public static function flatten(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $result = array_merge($result, static::flatten($item));
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Build a tree from a flat array of arrays or objects. 
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
     * Note: The array to build the tree from can be an array of arrays,
     * or an array of objects.
     * 
     * Example of an array of arrays to build a tree from:
     * 
     * $categories = [
     *    ['id' => 1, 'parent_id' => null, 'name' => 'Category 1'],
     *    ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
     *    ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
     *    ['id' => 4, 'parent_id' => 2, 'name' => 'Category 4'],
     *    ['id' => 5, 'parent_id' => null, 'name' => 'Category 5'],
     * ];
     * 
     * $tree = Arr::tree($categories);
     * 
     * Another example: In case you want to use a different key name for 
     * the ID and the parent ID, you can do:
     * 
     * $categories = [
     *    ['category_id' => 1, 'category_parent_id' => null, 'name' => 'Category 1'],
     *    ['category_id' => 2, 'category_parent_id' => 1, 'name' => 'Category 2'],
     *    ['category_id' => 3, 'category_parent_id' => 1, 'name' => 'Category 3'],
     *    ['category_id' => 4, 'category_parent_id' => 2, 'name' => 'Category 4'],
     *    ['category_id' => 5, 'category_parent_id' => null, 'name' => 'Category 5'],
     * ];
     * 
     * $tree = Arr::tree($categories, null, 'category_id', 'category_parent_id');
     */
    public static function tree(array $items, $parentId = null, string $idKey = 'id', string $parentIdKey = 'parent_id'): array
    {
        $result = [];

        foreach ($items as $key => $item) {
            if (is_array($item) && $item[$parentIdKey] == $parentId) {
                $result[$key] = $item;
                $result[$key]['children'] = self::tree($items, $item[$idKey], $idKey, $parentIdKey);
            } elseif (is_object($item) && $item->{$parentIdKey} == $parentId) {
                $result[$key] = $item;
                $result[$key]->children = self::tree($items, $item->{$idKey}, $idKey, $parentIdKey);
            }
        }

        return $result;
    }
}
