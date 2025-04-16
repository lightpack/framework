<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Cache\DriverInterface;
use Lightpack\Redis\Redis;

class RedisDriver implements DriverInterface
{
    /**
     * Redis client instance
     */
    protected Redis $redis;
    
    /**
     * Cache key prefix
     */
    protected string $prefix;
    
    /**
     * Constructor
     */
    public function __construct(Redis $redis, string $prefix = 'cache:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    
    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($this->prefix . $key);
    }
    
    /**
     * Get cached value
     */
    public function get(string $key)
    {
        $value = $this->redis->get($this->prefix . $key);
        
        if ($value === null) {
            return null;
        }
        
        return $value;
    }
    
    /**
     * Set cache value
     * 
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int $lifetime TTL timestamp or seconds
     * @param bool $preserveTtl Whether to preserve existing TTL
     */
    public function set(string $key, $value, int $lifetime, bool $preserveTtl = false)
    {
        $key = $this->prefix . $key;
        
        if ($preserveTtl && $this->redis->exists($key)) {
            // Keep existing TTL
            $ttl = $this->redis->ttl($key);
            if ($ttl > 0) {
                $this->redis->set($key, $value);
                $this->redis->expire($key, $ttl);
                return true;
            }
        }
        
        // Calculate TTL in seconds
        if ($lifetime > time()) {
            // If $lifetime is a timestamp, convert to seconds from now
            $ttl = $lifetime - time();
        } else {
            // If $lifetime is already in seconds or 0 (forever)
            $ttl = $lifetime;
        }
        
        if ($ttl <= 0) {
            // Store forever
            return $this->redis->set($key, $value);
        }
        
        // Store with expiration
        return $this->redis->set($key, $value, $ttl);
    }
    
    /**
     * Delete cache key
     */
    public function delete($key)
    {
        if (is_array($key)) {
            $keys = array_map(function($k) {
                return $this->prefix . $k;
            }, $key);
            
            return $this->redis->deleteMultiple($keys);
        }
        
        return $this->redis->delete($this->prefix . $key);
    }
    
    /**
     * Flush all cache
     */
    public function flush()
    {
        $keys = $this->redis->keys($this->prefix . '*');
        
        if (empty($keys)) {
            return true;
        }
        
        return $this->redis->deleteMultiple($keys);
    }
}
