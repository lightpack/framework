<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Cache\DriverInterface;

class ArrayDriver implements DriverInterface
{
    private $cache = [];

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    public function get($key)
    {
        return $this->cache[$key] ?? null;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->cache[$key]);
    }

    public function flush()
    {
        $this->cache = [];
    }
}