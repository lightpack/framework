<?php

namespace Lightpack\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Container\Container;

class Limiter 
{
    protected Cache $cache;

    public function __construct() 
    {
        $this->cache = Container::getInstance()->get('cache');
    }

    public function attempt(string $key, int $max, int $mins): bool 
    {
        $hits = (int) ($this->cache->get($key) ?? 0);
        
        if ($hits >= $max) {
            return false;
        }

        // First hit sets TTL, subsequent hits preserve it
        $this->cache->set($key, $hits + 1, $mins * 60, $hits > 0);
        return true;
    }
}
