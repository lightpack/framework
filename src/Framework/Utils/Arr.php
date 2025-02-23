<?php

declare(strict_types=1);

namespace Lightpack\Utils;

class Arr
{
    /**
     * Check if array has a key using dot notation.
     */
    public function has(string $key, array $data): bool
    {
        $keys = explode('.', $key);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return false;
            }

            $current = $current[$key];
        }

        return true;
    }

    /**
     * Get array value using dot notation.
     * Supports wildcard (*) for matching multiple elements.
     */
    public function get(string $key, array $data, $default = null)
    {
        if (strpos($key, '*') !== false) {
            return $this->getWildcard($key, $data, $default);
        }

        $keys = explode('.', $key);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }

            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Set array value using dot notation.
     */
    public function set(string $key, $value, array &$data): void
    {
        if (empty($key)) {
            throw new \ValueError('Key cannot be empty');
        }

        $keys = explode('.', $key);
        $current = &$data;

        foreach ($keys as $key) {
            if (!is_array($current)) {
                $current = [];
            }

            if (!array_key_exists($key, $current)) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current = $value;
    }

    /**
     * Delete array value using dot notation.
     */
    public function delete(string $key, array &$data): void
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return;
            }

            $current = &$current[$key];
        }

        if (is_array($current)) {
            unset($current[$lastKey]);
        }
    }

    /**
     * Internal method to handle wildcard array access.
     */
    private function getWildcard(string $key, array $data, $default = null)
    {
        $pattern = str_replace('*', '[^.]+', $key);
        $pattern = '#^' . str_replace('.', '\.', $pattern) . '$#';
        $results = [];

        $this->findWildcardMatches($pattern, $data, '', $results);

        if (empty($results)) {
            return $default;
        }

        return $results;
    }

    /**
     * Internal method to recursively find wildcard matches.
     */
    private function findWildcardMatches(string $pattern, array $data, string $currentPath, array &$results): void
    {
        foreach ($data as $key => $value) {
            $path = $currentPath ? $currentPath . '.' . $key : $key;

            if (is_array($value)) {
                $this->findWildcardMatches($pattern, $value, $path, $results);
            }

            if (preg_match($pattern, $path)) {
                $results[] = $value;
            }
        }
    }

    /**
     * Build a hierarchical tree structure from a flat array.
     * 
     * Example:
     * ```php
     * $items = [
     *    ['id' => 1, 'parent_id' => 0, 'name' => 'Category 1'],
     *    ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
     *    ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
     * ];
     * 
     * $tree = (new Arr)->tree($items);
     * ```
     * 
     * @param array $items The flat array of items
     * @param mixed $parentId The parent ID to start building from (default: 0)
     * @param string $idKey The key that contains the item's ID (default: 'id')
     * @param string $parentIdKey The key that contains the parent's ID (default: 'parent_id')
     * @return array The hierarchical tree structure
     */
    public function tree(array $items, $parentId = 0, string $idKey = 'id', string $parentIdKey = 'parent_id'): array
    {
        $tree = [];

        foreach ($items as $item) {
            $itemParentId = $item[$parentIdKey] ?? null;

            if ($itemParentId == $parentId) {
                $children = $this->tree($items, $item[$idKey], $idKey, $parentIdKey);

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * Transpose a column-based array to row-based array.
     * 
     * Example:
     * ```php
     * $data = [
     *     'name' => ['John', 'Jane'],
     *     'age' => [25, 30]
     * ];
     * 
     * $result = $arr->transpose($data);
     * // [
     * //     ['name' => 'John', 'age' => 25],
     * //     ['name' => 'Jane', 'age' => 30]
     * // ]
     * ```
     * 
     * @param array $array Column-based array to transpose
     * @param array $keys Optional specific keys to include
     * @return array Row-based array
     */
    public function transpose(array $array, array $keys = []): array
    {
        if (empty($array)) {
            return [];
        }

        if (empty($keys)) {
            $keys = array_keys($array);
        }

        $firstKey = reset($keys);
        if (!isset($array[$firstKey]) || !is_array($array[$firstKey])) {
            return [$array];
        }

        $result = [];
        $total = count($array[$firstKey]);

        for ($i = 0; $i < $total; $i++) {
            $item = [];
            foreach ($keys as $key) {
                if (isset($array[$key][$i])) {
                    $item[$key] = $array[$key][$i];
                }
            }
            if (!empty($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
