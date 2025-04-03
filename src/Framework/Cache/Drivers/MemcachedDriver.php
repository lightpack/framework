<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Cache\DriverInterface;
use Lightpack\Cache\Memcached;

class MemcachedDriver implements DriverInterface
{
    private Memcached $memcached;
    
    public function __construct(Memcached $memcached)
    {
        $this->memcached = $memcached;
    }
    
    public function has(string $key): bool
    {
        return $this->memcached->getClient()->get($key) !== false;
    }
    
    public function get(string $key)
    {
        return $this->memcached->getClient()->get($key);
    }
    
    public function set(string $key, $value, int $lifetime, bool $preserveTtl = false)
    {
        return $this->memcached->getClient()->set($key, $value, $lifetime);
    }
    
    public function delete($key)
    {
        return $this->memcached->getClient()->delete($key);
    }
    
    public function flush()
    {
        return $this->memcached->getClient()->flush();
    }
}
