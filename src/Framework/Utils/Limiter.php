<?php

namespace Lightpack\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Container\Container;

class Limiter 
{
    protected Cache $cache;
    protected string $prefix = 'limiter:';

    public function __construct() 
    {
        $this->cache = Container::getInstance()->get('cache');
    }

    /**
     * Attempt an action, limiting to $max times per $seconds window.
     *
     * @param string $key Unique key for the action/user
     * @param int $max Maximum allowed attempts in the window
     * @param int $seconds Cooldown window in seconds
     * @return bool True if allowed, false if rate limit exceeded
     */
    public function attempt(string $key, int $max, int $seconds): bool 
    {
        $key = $this->prefix . $key;
        $hits = (int) ($this->cache->get($key) ?? 0);
        
        if ($hits >= $max) {
            return false;
        }

        // First hit sets TTL, subsequent hits preserve it
        $this->cache->set($key, $hits + 1, $seconds, $hits > 0);
        return true;
    }

    public function getHits(string $key): ?int
    {
        return $this->cache->get($this->prefix . $key);
    }
}
