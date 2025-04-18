<?php

namespace Lightpack\Cable\Drivers;

/**
 * Redis Presence Driver
 * 
 * This driver uses Redis to track presence information,
 * providing high-performance for large-scale applications.
 */
class RedisPresenceDriver implements PresenceDriverInterface
{
    /**
     * @var \Redis
     */
    protected $redis;
    
    /**
     * @var string
     */
    protected $prefix = 'cable:presence:';
    
    /**
     * @var int
     */
    protected $timeout = 30; // seconds
    
    /**
     * Create a new Redis presence driver
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }
    
    /**
     * Set the key prefix
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    /**
     * Set the presence timeout in seconds
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }
    
    /**
     * Join a presence channel
     */
    public function join($userId, string $channel)
    {
        // Add to channel set
        $this->redis->sAdd($this->prefix . 'channel:' . $channel, $userId);
        
        // Add to user's channels set
        $this->redis->sAdd($this->prefix . 'user:' . $userId, $channel);
        
        // Set expiry for both keys
        $this->redis->expire($this->prefix . 'channel:' . $channel, $this->timeout);
        $this->redis->expire($this->prefix . 'user:' . $userId, $this->timeout);
    }
    
    /**
     * Leave a presence channel
     */
    public function leave($userId, string $channel)
    {
        // Remove from channel set
        $this->redis->sRem($this->prefix . 'channel:' . $channel, $userId);
        
        // Remove from user's channels set
        $this->redis->sRem($this->prefix . 'user:' . $userId, $channel);
    }
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat($userId, string $channel)
    {
        // Reset expiry for both keys
        $this->redis->expire($this->prefix . 'channel:' . $channel, $this->timeout);
        $this->redis->expire($this->prefix . 'user:' . $userId, $this->timeout);
    }
    
    /**
     * Get users present in a channel
     */
    public function getUsers(string $channel): array
    {
        return $this->redis->sMembers($this->prefix . 'channel:' . $channel) ?: [];
    }
    
    /**
     * Get channels a user is present in
     */
    public function getChannels($userId): array
    {
        return $this->redis->sMembers($this->prefix . 'user:' . $userId) ?: [];
    }
    
    /**
     * Clean up stale presence records
     * 
     * Note: Redis automatically handles expiry, so this is a no-op
     */
    public function cleanup(): int
    {
        // Redis handles expiry automatically via the TTL
        return 0;
    }
}
