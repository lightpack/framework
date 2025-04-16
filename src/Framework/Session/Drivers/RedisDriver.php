<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use Lightpack\Redis\Redis;

class RedisDriver implements DriverInterface
{
    /**
     * Redis client instance
     */
    protected Redis $redis;
    
    /**
     * Session name
     */
    protected string $name;
    
    /**
     * Session ID
     */
    protected string $id;
    
    /**
     * Session data
     */
    protected array $data = [];
    
    /**
     * Session lifetime in seconds
     */
    protected int $lifetime;
    
    /**
     * Flag to track if session has started
     */
    protected bool $started = false;
    
    /**
     * Session key prefix
     */
    protected string $prefix;
    
    /**
     * Constructor
     */
    public function __construct(Redis $redis, string $name, int $lifetime = 7200, string $prefix = 'session:')
    {
        $this->redis = $redis;
        $this->name = $name;
        $this->lifetime = $lifetime;
        $this->prefix = $prefix;
    }
    
    /**
     * Start the session
     */
    public function start()
    {
        if ($this->started) {
            return;
        }
        
        // Get session ID from cookie or create new one
        $this->id = $_COOKIE[$this->name] ?? $this->generateId();
        
        // Set session cookie
        $this->setCookie();
        
        // Load session data from Redis
        $data = $this->redis->get($this->getRedisKey());
        $this->data = $data ?: [];
        
        // Refresh session expiration
        $this->redis->expire($this->getRedisKey(), $this->lifetime);
        
        $this->started = true;
    }
    
    /**
     * Check if session has started
     */
    public function started(): bool
    {
        return $this->started;
    }
    
    /**
     * Set session value
     */
    public function set(string $key, $value)
    {
        if (!$this->started) {
            $this->start();
        }
        
        $this->data[$key] = $value;
        
        $this->redis->set($this->getRedisKey(), $this->data, $this->lifetime);
    }
    
    /**
     * Get session value
     */
    public function get(?string $key = null, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }
        
        if ($key === null) {
            return $this->data;
        }
        
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Delete session key
     */
    public function delete(string $key)
    {
        if (!$this->started) {
            $this->start();
        }
        
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            $this->redis->set($this->getRedisKey(), $this->data, $this->lifetime);
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate(): bool
    {
        if (!$this->started) {
            $this->start();
        }
        
        // Get current session data
        $data = $this->data;
        
        // Delete old session
        $this->redis->delete($this->getRedisKey());
        
        // Generate new session ID
        $this->id = $this->generateId();
        
        // Set new session cookie
        $this->setCookie();
        
        // Store data with new ID
        $this->data = $data;
        $this->redis->set($this->getRedisKey(), $this->data, $this->lifetime);
        
        return true;
    }
    
    /**
     * Destroy session
     */
    public function destroy()
    {
        if (!$this->started) {
            return;
        }
        
        // Delete session data from Redis
        $this->redis->delete($this->getRedisKey());
        
        // Clear session cookie
        $this->clearCookie();
        
        // Reset session data
        $this->data = [];
        $this->started = false;
    }
    
    /**
     * Generate unique session ID
     */
    protected function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Get Redis key for session
     */
    protected function getRedisKey(): string
    {
        return $this->prefix . $this->id;
    }
    
    /**
     * Set session cookie
     */
    protected function setCookie()
    {
        $params = session_get_cookie_params();
        
        setcookie(
            $this->name,
            $this->id,
            [
                'expires' => time() + $this->lifetime,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
        
        $_COOKIE[$this->name] = $this->id;
    }
    
    /**
     * Clear session cookie
     */
    protected function clearCookie()
    {
        $params = session_get_cookie_params();
        
        setcookie(
            $this->name,
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
        
        unset($_COOKIE[$this->name]);
    }
}
