<?php

namespace Lightpack\Cache;

class Cache
{
    private $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    public function get(string $key)
    {
        return $this->driver->get($key);
    }

    public function set(string $key, $value, int $seconds = 0)
    {
        $ttl = time() + ($seconds * 60);
        return $this->driver->set($key, $value, $ttl);
    }

    public function delete($key)
    {
        return $this->driver->delete($key);
    }

    public function forever(string $key, $value)
    {
        $lifetime = time() + (60 * 60 * 24 * 365 * 5);
        return $this->driver->set($key, $value, $lifetime);
    }

    public function flush()
    {
        return $this->driver->flush();
    }

    public function remember(string $key, int $seconds, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $seconds);

        return $value;
    }

    public function rememberForever(string $key, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->forever($key, $value);

        return $value;
    }
}
