<?php

namespace Lightpack\Cable\Drivers;

use Lightpack\Cable\CableDriverInterface;
use Lightpack\Redis\Redis;

/**
 * Redis Driver for Cable
 * 
 * This driver uses Redis to store and retrieve messages,
 * providing better performance for high-traffic applications.
 */
class RedisCableDriver implements CableDriverInterface
{
    /**
     * @var Redis
     */
    protected $redis;
    
    /**
     * @var string
     */
    protected $prefix;
    
    /**
     * Create a new Redis driver
     */
    public function __construct(Redis $redis, string $prefix = 'cable:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    
    /**
     * Emit an event to a channel
     */
    public function emit(string $channel, string $event, array $payload): void
    {
        $message = json_encode([
            'id' => (int)(microtime(true) * 1000), // Convert to milliseconds integer
            'event' => $event,
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Add to sorted set with score as timestamp
        $this->redis->zAdd(
            $this->getChannelKey($channel),
            (int)(microtime(true) * 1000), // Use same integer timestamp as score
            $message
        );
        
        // Set expiration for automatic cleanup
        $this->redis->expire($this->getChannelKey($channel), 86400);
    }
    
    /**
     * Get messages from a channel
     */
    public function getMessages(string $channel, ?int $lastId = null): array
    {
        // For exclusive range (> lastId), we need to add 1
        // This ensures we don't get duplicate messages when polling
        $score = $lastId ? ($lastId + 1) : '-inf';
        
        $messages = $this->redis->zRangeByScore(
            $this->getChannelKey($channel),
            $score,
            '+inf',
            ['limit' => [0, 100]]
        );
        
        $result = [];
        foreach ($messages as $message) {
            $data = json_decode($message, true);
            
            // Convert to object for consistency with database driver
            $messageObj = (object) $data;
            
            // Add to result
            $result[] = $messageObj;
        }
        
        return $result;
    }
    
    /**
     * Clean up old messages
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        $channels = $this->redis->keys($this->prefix . '*');
        
        foreach ($channels as $channel) {
            $this->redis->zRemRangeByScore(
                $channel,
                '-inf',
                microtime(true) - $olderThanSeconds
            );
        }
    }
    
    /**
     * Get Redis key for channel
     */
    protected function getChannelKey(string $channel): string
    {
        return $this->prefix . $channel;
    }
}
