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

    public function set(string $key, $value, int $seconds = 0, bool $preserveTtl = false)
    {
        if ($preserveTtl) {
            // Pass 0 to driver to preserve existing TTL
            return $this->driver->set($key, $value, 0, true);
        }

        // Calculate new TTL
        if ($seconds === 0) {
            $ttl = time() + 157680000; // 5 years
        } else {
            $ttl = time() + $seconds;
        }

        return $this->driver->set($key, $value, $ttl, false);
    }

    public function delete($key)
    {
        return $this->driver->delete($key);
    }

    public function forever(string $key, $value)
    {
        return $this->set($key, $value, 0);
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
