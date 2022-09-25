<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Cache\DriverInterface;

class NullDriver implements DriverInterface
{
     public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key)
    {
        return null;
    }

    public function set(string $key, $value, int $ttl)
    {
        return true;
    }

    public function delete($key)
    {
        return true;
    }

    public function flush()
    {
        return true;
    }
}