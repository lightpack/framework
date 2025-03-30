<?php

namespace Lightpack\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Container\Container;

class Lock 
{
    protected Cache $cache;
    protected string $prefix = 'lock:';
    
    public function __construct() 
    {
        $this->cache = Container::getInstance()->get('cache');
    }
    
    public function acquire(string $key, int $seconds = 60): bool
    {
        $key = $this->prefix . $key;
        
        if ($this->cache->has($key)) {
            return false;
        }
        
        $this->cache->set($key, true, $seconds, false);
        return true;
    }
    
    public function release(string $key)
    {
        $this->cache->delete($this->prefix . $key);
    }
    
    public function has(string $key): bool 
    {
        return $this->cache->has($this->prefix . $key);
    }
}
