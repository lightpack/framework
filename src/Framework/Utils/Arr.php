<?php

namespace Lightpack\Utils;

use InvalidArgumentException;
use ValueError;

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
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * (new Arr)->get('a.b.c', $array) === 'd';
     */
    public function get(string $key, array $array, $default = null)
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
    public function flatten(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $this->flatten($item));
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
     * $tree = (new Arr)->tree($categories);
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
     * $tree = (new Arr)->tree($categories, null, 'category_id', 'category_parent_id');
     */
    public function tree(array $items, $parentId = null, string $idKey = 'id', string $parentIdKey = 'parent_id'): array
    {
        $result = [];

        foreach ($items as $key => $item) {
            if (is_array($item) && $item[$parentIdKey] == $parentId) {
                $result[$key] = $item;
                $result[$key]['children'] = $this->tree($items, $item[$idKey], $idKey, $parentIdKey);
            } elseif (is_object($item) && $item->{$parentIdKey} == $parentId) {
                $result[$key] = $item;
                $result[$key]->children = $this->tree($items, $item->{$idKey}, $idKey, $parentIdKey);
            }
        }

        return $result;
    }

    /**
     * Get one or more random items from an array.
     * 
     * @param array $items The source array.
     * @param int $number Number of items to pick.
     * @param bool $preserveKeys Preserve item keys in the fetched random items.
     * 
     * @return mixed One or more array items. If an empty array is passed, it return boolean FALSE.
     */
    public function random(array $items, int $number = 1, bool $preserveKeys = false): mixed
    {
        $itemsCount = count($items);

        if (empty($items)) {
            throw new ValueError("You cannot pass an empty array of items.");
        }

        if ($number > $itemsCount) {
            throw new ValueError("You cannot request more than {$itemsCount} items.");
        }

        if ($number < 1) {
            throw new ValueError("You cannot request less than 1 item.");
        }

        if ($number == 1) {
            return $items[array_rand($items)];
        }

        $randomItems = [];
        $randomKeys = array_rand($items, $number);

        foreach ($randomKeys as $key) {
            if ($preserveKeys) {
                $randomItems[$key] = $items[$key];
            } else {
                $randomItems[] = $items[$key];
            }
        }

        return $randomItems;
    }

    /**
     * Groups the items of an array by a given key.
     *
     * @param string $key The key by which to group the items.
     * @param array $items The array or collection of items to be grouped.
     * @param bool $preserveKeys (optional) Whether to preserve the keys of the grouped items. Defaults to false.
     * @return array The grouped array with keys representing the grouping criteria and values representing the grouped items.
     */
    function groupBy(string $key, array $items, bool $preserveKeys = false)
    {
        $grouped = [];

        foreach ($items as $index => $item) {
            if (array_key_exists($key, $item)) {
                $groupKey = $item[$key];

                if ($preserveKeys) {
                    $grouped[$groupKey][$index] = $item;
                } else {
                    $grouped[$groupKey][] = $item;
                }
            }
        }

        return $grouped;
    }
}
