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
     * Deep merge two arrays recursively.
     * 
     * For example:
     * $array1 = ['a' => ['b' => 2], 'c' => 3];
     * $array2 = ['a' => ['b' => 4, 'd' => 5]];
     * (new Arr)->merge($array1, $array2);
     * // Result: ['a' => ['b' => 4, 'd' => 5], 'c' => 3]
     */
    public function merge(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->merge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * Pluck a specific key from an array of arrays or objects.
     * 
     * For example:
     * $items = [
     *    ['id' => 1, 'name' => 'John'],
     *    ['id' => 2, 'name' => 'Jane']
     * ];
     * (new Arr)->pluck('name', $items); // ['John', 'Jane']
     * (new Arr)->pluck('name', $items, 'id'); // [1 => 'John', 2 => 'Jane']
     * 
     * @param string $key The key to pluck
     * @param array $array Array of arrays/objects to pluck from
     * @param string|null $keyBy Optional key to index the results by
     * @throws ValueError When key is empty
     */
    public function pluck(string $key, array $array, ?string $keyBy = null): array
    {
        if (empty($key)) {
            throw new ValueError('Key cannot be empty');
        }

        $result = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                if (!isset($item[$key])) {
                    continue;
                }
                
                if ($keyBy !== null && isset($item[$keyBy])) {
                    $result[$item[$keyBy]] = $item[$key];
                } else {
                    $result[] = $item[$key];
                }
            } elseif (is_object($item)) {
                if (!isset($item->{$key})) {
                    continue;
                }
                
                if ($keyBy !== null && isset($item->{$keyBy})) {
                    $result[$item->{$keyBy}] = $item->{$key};
                } else {
                    $result[] = $item->{$key};
                }
            }
        }

        return $result;
    }

    /**
     * Shuffle an array preserving keys optionally.
     * 
     * For example:
     * $items = ['a' => 1, 'b' => 2, 'c' => 3];
     * (new Arr)->shuffle($items); // [2, 1, 3]
     * (new Arr)->shuffle($items, true); // ['b' => 2, 'a' => 1, 'c' => 3]
     * (new Arr)->shuffle([]); // []
     * 
     * @param array $array Array to shuffle
     * @param bool $preserveKeys Whether to preserve the keys
     * @return array Shuffled array or empty array if input is empty
     */
    public function shuffle(array $array, bool $preserveKeys = false): array
    {
        if (empty($array)) {
            return [];
        }

        if ($preserveKeys) {
            $keys = array_keys($array);
            $values = array_values($array);
            shuffle($values);
            
            return array_combine($keys, $values);
        }

        $items = $array;
        shuffle($items);
        return $items;
    }

    /**
     * Split an array into smaller chunks of specified size.
     * 
     * This method is particularly useful for:
     * 1. Batch Processing: Process large datasets in smaller chunks
     *    ```php
     *    foreach ((new Arr)->chunk($users, 100) as $batch) {
     *        processUserBatch($batch);
     *    }
     *    ```
     * 
     * 2. Pagination: Display items in pages
     *    ```php
     *    $itemsPerPage = 10;
     *    $pages = (new Arr)->chunk($items, $itemsPerPage);
     *    $currentPage = $pages[$pageNumber] ?? [];
     *    ```
     * 
     * 3. Grid Layouts: Create rows with fixed number of columns
     *    ```php
     *    $columns = 3;
     *    $rows = (new Arr)->chunk($products, $columns);
     *    foreach ($rows as $row) {
     *        echo '<div class="row">';
     *        foreach ($row as $product) {
     *            echo '<div class="col">' . $product['name'] . '</div>';
     *        }
     *        echo '</div>';
     *    }
     *    ```
     * 
     * 4. Rate Limiting: Process API calls in controlled batches
     *    ```php
     *    foreach ((new Arr)->chunk($apiCalls, 5) as $batch) {
     *        processBatch($batch);
     *        sleep(1); // Rate limit: 5 calls per second
     *    }
     *    ```
     * 
     * @param array $array The input array to chunk
     * @param int $size The size of each chunk
     * @param bool $preserveKeys Whether to preserve the keys in the chunks
     * @throws ValueError When size is less than 1
     * @return array An array containing the chunks
     */
    public function chunk(array $array, int $size, bool $preserveKeys = false): array
    {
        if ($size < 1) {
            throw new ValueError('Chunk size must be greater than 0');
        }

        if (empty($array)) {
            return [];
        }

        return array_chunk($array, $size, $preserveKeys);
    }

    /**
     * Group an array by multiple keys, creating a deeply nested structure.
     * 
     * This method is incredibly powerful for:
     * 1. Hierarchical Data Organization:
     *    ```php
     *    $users = [
     *        ['country' => 'USA', 'state' => 'CA', 'name' => 'John'],
     *        ['country' => 'USA', 'state' => 'NY', 'name' => 'Jane']
     *    ];
     *    $byLocation = (new Arr)->groupByMultiple($users, ['country', 'state']);
     *    // Access: $byLocation['USA']['CA'][0]['name'] // "John"
     *    ```
     * 
     * 2. Multi-level Category Trees:
     *    ```php
     *    $products = [
     *        ['category' => 'Electronics', 'type' => 'Laptop', 'brand' => 'Dell'],
     *        ['category' => 'Electronics', 'type' => 'Phone', 'brand' => 'Apple']
     *    ];
     *    $tree = (new Arr)->groupByMultiple($products, ['category', 'type', 'brand']);
     *    ```
     * 
     * 3. Sales/Analytics Reports:
     *    ```php
     *    $sales = [
     *        ['year' => 2023, 'quarter' => 'Q1', 'region' => 'West', 'amount' => 1000],
     *        ['year' => 2023, 'quarter' => 'Q1', 'region' => 'East', 'amount' => 1500]
     *    ];
     *    $report = (new Arr)->groupByMultiple($sales, ['year', 'quarter', 'region']);
     *    ```
     * 
     * 4. Dynamic Filter Systems:
     *    ```php
     *    $items = [
     *        ['status' => 'active', 'priority' => 'high', 'type' => 'bug'],
     *        ['status' => 'active', 'priority' => 'low', 'type' => 'feature']
     *    ];
     *    $filters = (new Arr)->groupByMultiple($items, ['status', 'priority', 'type']);
     *    ```
     * 
     * @param array $array The input array to group
     * @param array $keys The keys to group by, in order of hierarchy
     * @throws ValueError When keys array is empty
     * @return array The grouped array
     */
    public function groupByMultiple(array $array, array $keys): array
    {
        if (empty($keys)) {
            throw new ValueError('Keys array cannot be empty');
        }

        if (empty($array)) {
            return [];
        }

        $result = [];
        
        foreach ($array as $item) {
            $current = &$result;
            
            foreach ($keys as $key) {
                $value = is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
                if ($value === null) {
                    continue 2; // Skip items with missing keys
                }
                
                if (!isset($current[$value])) {
                    $current[$value] = [];
                }
                
                $current = &$current[$value];
            }
            
            $current[] = $item;
        }
        
        return $result;
    }
}
