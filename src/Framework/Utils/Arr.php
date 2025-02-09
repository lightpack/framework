<?php

namespace Lightpack\Utils;

use InvalidArgumentException;
use ValueError;
use DateTime;
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
     * Get value from array or object using 'dot' notation.
     * 
     * For example:
     * ```php
     * $array = ['a' => ['b' => ['c' => 'd']]];
     * (new Arr)->get('a.b.c', $array) === 'd';
     * 
     * $object = (object)['a' => (object)['b' => 'c']];
     * (new Arr)->get('a.b', $object) === 'c';
     * ```
     */
    public function get(string $key, array|object $data, $default = null)
    {
        $keys = explode('.', $key);
        $current = $data;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (is_array($current)) {
                if (!isset($current[$key]) || (!is_array($current[$key]) && !is_object($current[$key]))) {
                    return $default;
                }
                $current = $current[$key];
            } elseif (is_object($current)) {
                if (!property_exists($current, $key) || (!is_array($current->$key) && !is_object($current->$key))) {
                    return $default;
                }
                $current = $current->$key;
            } else {
                return $default;
            }
        }

        $key = array_shift($keys);

        if (is_array($current)) {
            return $current[$key] ?? $default;
        } elseif (is_object($current)) {
            return property_exists($current, $key) ? $current->$key : $default;
        }

        return $default;
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
     * Groups the items of an array by one or more keys.
     * 
     * This method supports both single-level and multi-level grouping:
     * 
     * Single level grouping:
     * ```php
     * $users = [
     *    ['name' => 'John', 'age' => 30],
     *    ['name' => 'Jane', 'age' => 30]
     * ];
     * $byAge = (new Arr)->groupBy('age', $users);
     * // Result: ['30' => [['name' => 'John', 'age' => 30], ['name' => 'Jane', 'age' => 30]]]
     * ```
     * 
     * Multi-level grouping:
     * ```php
     * $users = [
     *    ['country' => 'USA', 'state' => 'CA', 'name' => 'John'],
     *    ['country' => 'USA', 'state' => 'NY', 'name' => 'Jane']
     * ];
     * $grouped = (new Arr)->groupBy(['country', 'state'], $users);
     * // Result: ['USA' => ['CA' => [...], 'NY' => [...]]]
     * ```
     * 
     * @param string|array $keys The key(s) to group by. Can be a string for single level or array for multi-level grouping
     * @param array $items The array of items to group
     * @param bool $preserveKeys Whether to preserve the keys of the grouped items
     * @return array The grouped array
     * @throws ValueError When key is empty or keys array is empty
     */
    public function groupBy($keys, array $items, bool $preserveKeys = false): array
    {
        if (empty($keys)) {
            throw new ValueError('Key or keys array cannot be empty');
        }

        // Convert string key to array for consistent processing
        $keys = (array) $keys;

        if (count($keys) === 1) {
            // Single level grouping
            $key = $keys[0];
            $grouped = [];

            foreach ($items as $index => $item) {
                if (!isset($item[$key])) {
                    continue;
                }

                $groupKey = $item[$key];
                if ($preserveKeys) {
                    $grouped[$groupKey][$index] = $item;
                } else {
                    $grouped[$groupKey][] = $item;
                }
            }

            return $grouped;
        }

        // Multi-level grouping
        $firstKey = array_shift($keys);
        $grouped = $this->groupBy($firstKey, $items, $preserveKeys);

        foreach ($grouped as $key => &$group) {
            $group = $this->groupBy($keys, $group, $preserveKeys);
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
     * Compare arrays and return the differences.
     * 
     * This method provides three types of differences:
     * 1. Added   - Items present in array2 but not in array1
     * 2. Removed - Items present in array1 but not in array2
     * 3. Modified - Items present in both but with different values
     * 
     * Basic usage with simple arrays:
     * ```php
     * $array1 = ['a' => 1, 'b' => 2];
     * $array2 = ['b' => 3, 'c' => 4];
     * $diff = (new Arr)->diff($array1, $array2);
     * // Result: [
     * //     'added'    => ['c' => 4],
     * //     'removed'  => ['a' => 1],
     * //     'modified' => ['b' => ['old' => 2, 'new' => 3]]
     * // ]
     * ```
     * 
     * Advanced usage with nested arrays:
     * ```php
     * $users1 = [
     *     ['id' => 1, 'name' => 'John', 'age' => 30],
     *     ['id' => 2, 'name' => 'Jane', 'age' => 25]
     * ];
     * $users2 = [
     *     ['id' => 1, 'name' => 'John', 'age' => 31],
     *     ['id' => 3, 'name' => 'Bob', 'age' => 35]
     * ];
     * $diff = (new Arr)->diff($users1, $users2, 'id');
     * // Result: [
     * //     'added'    => [['id' => 3, 'name' => 'Bob', 'age' => 35]],
     * //     'removed'  => [['id' => 2, 'name' => 'Jane', 'age' => 25]],
     * //     'modified' => [
     * //         1 => [
     * //             'old' => ['id' => 1, 'name' => 'John', 'age' => 30],
     * //             'new' => ['id' => 1, 'name' => 'John', 'age' => 31]
     * //         ]
     * //     ]
     * // ]
     * ```
     * 
     * @param array $array1 The first array to compare
     * @param array $array2 The second array to compare
     * @param string|null $key Optional key to use as unique identifier for comparing array items
     * @return array An array containing 'added', 'removed', and 'modified' items
     */
    public function diff(array $array1, array $array2, ?string $key = null): array
    {
        if ($key !== null) {
            // Convert arrays to associative arrays using the key
            $array1 = array_column($array1, null, $key);
            $array2 = array_column($array2, null, $key);
        }

        $added = array_diff_key($array2, $array1);
        $removed = array_diff_key($array1, $array2);
        $modified = [];

        // Find modified items (present in both but different)
        foreach (array_intersect_key($array1, $array2) as $k => $v1) {
            $v2 = $array2[$k];

            // Handle nested arrays recursively
            if (is_array($v1) && is_array($v2)) {
                $nested_diff = $this->diff($v1, $v2);
                if (!empty($nested_diff['added']) || !empty($nested_diff['removed']) || !empty($nested_diff['modified'])) {
                    $modified[$k] = ['old' => $v1, 'new' => $v2];
                }
            }
            // Compare non-array values
            elseif ($v1 !== $v2) {
                $modified[$k] = ['old' => $v1, 'new' => $v2];
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
        ];
    }

    /**
     * Cast array values to specified types.
     * 
     * Supported types:
     * - 'int': Cast to integer
     * - 'bool': Cast to boolean
     * - 'float': Cast to float
     * - 'string': Cast to string
     * - 'array': Cast to array (comma-separated string to array)
     * - 'datetime': Cast to DateTime object
     * - 'json': Decode JSON string to array/object
     * 
     * Example usage:
     * ```php
     * $data = [
     *     'id' => '1',
     *     'active' => '1',
     *     'score' => '99.9',
     *     'tags' => 'php,web,dev',
     *     'meta' => '{"type": "user"}',
     *     'created_at' => '2024-01-01'
     * ];
     * 
     * $casted = (new Arr)->cast($data, [
     *     'id' => 'int',
     *     'active' => 'bool',
     *     'score' => 'float',
     *     'tags' => 'array',
     *     'meta' => 'json',
     *     'created_at' => 'datetime'
     * ]);
     * ```
     * 
     * @param array $array The input array to cast
     * @param array $types Map of field names to their desired types
     * @return array The array with casted values
     * @throws ValueError If an unsupported type is specified
     * @throws JsonException If JSON decoding fails
     */
    public function cast(array $array, array $types): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            // Skip if no type casting is specified for this key
            if (!isset($types[$key])) {
                $result[$key] = $value;
                continue;
            }

            $type = strtolower($types[$key]);
            
            // Skip null values
            if ($value === null) {
                $result[$key] = null;
                continue;
            }

            // Cast based on type
            switch ($type) {
                case 'int':
                    $result[$key] = (int) $value;
                    break;

                case 'bool':
                    // Handle various boolean representations
                    if (is_string($value)) {
                        $value = strtolower($value);
                        $result[$key] = in_array($value, ['1', 'true', 'yes', 'on'], true);
                    } else {
                        $result[$key] = (bool) $value;
                    }
                    break;

                case 'float':
                    $result[$key] = (float) $value;
                    break;

                case 'string':
                    $result[$key] = (string) $value;
                    break;

                case 'array':
                    if (is_string($value)) {
                        $result[$key] = array_map('trim', explode(',', $value));
                    } elseif (is_array($value)) {
                        $result[$key] = $value;
                    } else {
                        $result[$key] = [$value];
                    }
                    break;

                case 'datetime':
                    if ($value instanceof DateTime) {
                        $result[$key] = $value;
                    } else {
                        try {
                            $result[$key] = new DateTime($value);
                        } catch (\Exception|\Error $e) {
                            throw new ValueError(
                                "Failed to cast '{$key}' to datetime: Invalid date format"
                            );
                        }
                    }
                    break;

                case 'json':
                    if (is_string($value)) {
                        try {
                            $result[$key] = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                        } catch (JsonException $e) {
                            throw new ValueError(
                                "Failed to decode JSON for '{$key}': " . $e->getMessage()
                            );
                        }
                    } else {
                        $result[$key] = $value;
                    }
                    break;

                default:
                    throw new ValueError("Unsupported cast type: {$type}");
            }
        }

        return $result;
    }

    /**
     * Pick specific fields from an array or array of arrays/objects.
     * 
     * Features:
     * 1. Pick multiple fields: ['id', 'name']
     * 2. Rename fields: ['newName' => 'oldName']
     * 3. Pick nested fields: ['user.name', 'address.city']
     * 4. Transform values: ['age' => ['from' => 'birth_year', 'transform' => fn($v) => date('Y') - $v]]
     * 5. Default values: ['status' => ['from' => 'state', 'default' => 'active']]
     * 
     * Example usage:
     * ```php
     * $user = [
     *     'id' => 1,
     *     'user' => ['name' => 'John', 'age' => 30],
     *     'address' => ['city' => 'NY', 'country' => 'USA'],
     *     'birth_year' => 1990
     * ];
     * 
     * $picked = (new Arr)->pick($user, [
     *     'id',                                    // Simple pick
     *     'name' => 'user.name',                  // Nested pick with rename
     *     'city' => 'address.city',               // Nested pick with rename
     *     'age' => [                              // Transform with callback
     *         'from' => 'birth_year',
     *         'transform' => fn($v) => date('Y') - $v
     *     ],
     *     'status' => [                           // Pick with default value
     *         'from' => 'account_status',
     *         'default' => 'active'
     *     ]
     * ]);
     * 
     * // Result:
     * // [
     * //     'id' => 1,
     * //     'name' => 'John',
     * //     'city' => 'NY',
     * //     'age' => 33,
     * //     'status' => 'active'
     * // ]
     * ```
     * 
     * @param array|object $data The source data to pick from
     * @param array $fields Fields to pick. Can be string keys for renaming or integer keys for direct picking
     * @return array The resulting array with picked fields
     * @throws ValueError If a required field is missing and no default is provided
     */
    public function pick(array|object $data, array $fields): array
    {
        $result = [];
        $data = (array) $data;

        foreach ($fields as $key => $field) {
            // Handle simple picks: ['id', 'name']
            if (is_int($key)) {
                $key = $field;
                $value = $this->get($field, $data);
                if ($value !== null) {
                    $result[$key] = $value;
                }
                continue;
            }

            // Handle callable transformations directly: ['email' => 'trim']
            if (is_callable($field)) {
                $value = $this->get($key, $data);
                if ($value !== null) {
                    $result[$key] = $field($value);
                }
                continue;
            }

            // Handle string path: 'user.name' => 'name'
            if (is_string($field)) {
                $value = $this->get($field, $data);
                if ($value !== null) {
                    $result[$key] = $value;
                }
                continue;
            }

            // Handle complex picks with options
            if (is_array($field)) {
                $from = $field['from'] ?? $key;
                $default = array_key_exists('default', $field) ? $field['default'] : null;
                $value = $this->get($from, $data, $default);

                // Apply transform if provided
                if (isset($field['transform'])) {
                    if (is_callable($field['transform'])) {
                        $value = $field['transform']($value);
                    } elseif (is_string($field['transform']) && is_callable($field['transform'])) {
                        $value = call_user_func($field['transform'], $value);
                    }
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Partition an array into two arrays based on a callback.
     * 
     * The first array contains items that passed the callback condition,
     * the second array contains items that failed the condition.
     * 
     * Example usage:
     * ```php
     * $numbers = [1, 2, 3, 4, 5];
     * [$evens, $odds] = (new Arr)->partition($numbers, fn($n) => $n % 2 === 0);
     * 
     * $users = [
     *    ['name' => 'John', 'active' => true],
     *    ['name' => 'Jane', 'active' => false]
     * ];
     * [$active, $inactive] = (new Arr)->partition($users, fn($user) => $user['active']);
     * ```
     * 
     * @param array $array The input array
     * @param callable $callback Function that returns true/false for each item
     *                          Callback receives (mixed $value, int|string $key)
     * @param bool $preserveKeys Whether to preserve the original array keys
     * @return array Array containing two arrays: [passed items, failed items]
     */
    public function partition(array $array, callable $callback, bool $preserveKeys = true): array
    {
        $passed = [];
        $failed = [];

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                if ($preserveKeys) {
                    $passed[$key] = $value;
                } else {
                    $passed[] = $value;
                }
            } else {
                if ($preserveKeys) {
                    $failed[$key] = $value;
                } else {
                    $failed[] = $value;
                }
            }
        }

        return [$passed, $failed];
    }
}
