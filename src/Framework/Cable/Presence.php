<?php

namespace Lightpack\Cable;

use Lightpack\Cable\Drivers\PresenceDriverInterface;

/**
 * Presence
 * 
 * This class manages presence channels, tracking which users are online
 * in real-time channels and broadcasting presence updates.
 */
class Presence
{
    /**
     * @var Cable
     */
    protected $cable;
    
    /**
     * @var Lightpack\Cable\Drivers\PresenceDriverInterface
     */
    protected $driver;
    
    /**
     * Create a new presence manager
     */
    public function __construct(Cable $cable, PresenceDriverInterface $driver)
    {
        $this->cable = $cable;
        $this->driver = $driver;
    }
    
    /**
     * Join a presence channel
     */
    public function join($userId, string $channel): self
    {
        $this->driver->join($userId, $channel);
        $this->broadcast($channel);
        
        return $this;
    }
    
    /**
     * Leave a presence channel
     */
    public function leave($userId, string $channel): self
    {
        $this->driver->leave($userId, $channel);
        $this->broadcast($channel);
        
        return $this;
    }
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat($userId, string $channel): self
    {
        $this->driver->heartbeat($userId, $channel);
        return $this;
    }
    
    /**
     * Get users present in a channel
     */
    public function getUsers(string $channel): array
    {
        return $this->driver->getUsers($channel);
    }
    
    /**
     * Get channels a user is present in
     */
    public function getChannels($userId): array
    {
        return $this->driver->getChannels($userId);
    }
    
    /**
     * Broadcast presence update to a channel
     */
    protected function broadcast(string $channel): void
    {
        $users = $this->getUsers($channel);
        
        $this->cable->to($channel)->emit('presence:update', [
            'users' => $users,
            'count' => count($users),
            'timestamp' => time(),
        ]);
    }
    
    /**
     * Clean up stale presence records
     */
    public function cleanup(): void
    {
        $this->driver->cleanup();
    }
}