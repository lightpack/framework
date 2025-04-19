<?php

namespace Lightpack\Redis;

class Redis
{
    /**
     * Redis connection instance
     * 
     * @var \Redis|null
     */
    protected $connection;
    
    /**
     * Redis connection parameters
     *
     * @var array
     */
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 0.0,
        'read_timeout' => 0.0,
        'retry_interval' => 0,
        'prefix' => '',
    ];
    
    /**
     * Constructor
     *
     * @param array $config Redis connection configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Get Redis connection
     * 
     * @return \Redis
     */
    public function connection()
    {
        if (!$this->connection) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Connect to Redis server
     *
     * @return self
     * @throws \RuntimeException If Redis extension is not loaded
     */
    public function connect()
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded');
        }
        
        $this->connection = new \Redis();
        
        $this->connection->connect(
            $this->config['host'],
            $this->config['port'],
            $this->config['timeout'],
            null,
            $this->config['retry_interval']
        );
        
        if ($this->config['read_timeout']) {
            $this->connection->setOption(\Redis::OPT_READ_TIMEOUT, $this->config['read_timeout']);
        }
        
        if ($this->config['password']) {
            $this->connection->auth($this->config['password']);
        }
        
        if ($this->config['database']) {
            $this->connection->select($this->config['database']);
        }
        
        if ($this->config['prefix']) {
            $this->connection->setOption(\Redis::OPT_PREFIX, $this->config['prefix']);
        }
        
        return $this;
    }
    
    /**
     * Check if key exists
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return (bool) $this->connection()->exists($key);
    }
    
    /**
     * Get value by key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->connection()->get($key);
        
        if ($value === false) {
            return null;
        }
        
        return $this->unserialize($value);
    }
    
    /**
     * Set key value pair
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $value = $this->serialize($value);
        
        if ($ttl) {
            return $this->connection()->setex($key, $ttl, $value);
        }
        
        return $this->connection()->set($key, $value);
    }
    
    /**
     * Delete key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return (bool) $this->connection()->del($key);
    }
    
    /**
     * Delete multiple keys
     *
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        if (empty($keys)) {
            return true;
        }
        
        return (bool) $this->connection()->del(...$keys);
    }
    
    /**
     * Increment value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return $this->connection()->incrBy($key, $value);
    }
    
    /**
     * Decrement value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->connection()->decrBy($key, $value);
    }
    
    /**
     * Set expiration time for key
     *
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire($key, $ttl)
    {
        return $this->connection()->expire($key, $ttl);
    }
    
    /**
     * Get time to live for key
     *
     * @param string $key
     * @return int
     */
    public function ttl($key)
    {
        return $this->connection()->ttl($key);
    }
    
    /**
     * Flush database
     *
     * @return bool
     */
    public function flush()
    {
        return $this->connection()->flushDB();
    }
    
    /**
     * Get all keys matching pattern
     *
     * @param string $pattern
     * @return array
     */
    public function keys($pattern)
    {
        return $this->connection()->keys($pattern);
    }
    
    /**
     * Serialize value
     *
     * @param mixed $value
     * @return string|int|float
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }
    
    /**
     * Unserialize value
     *
     * @param string $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        
        $unserializedValue = @unserialize($value);
        
        return $unserializedValue === false ? $value : $unserializedValue;
    }
    
    /**
     * Magic method to pass calls to the Redis connection
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->connection()->$method(...$arguments);
    }
}
