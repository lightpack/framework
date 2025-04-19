<?php

namespace Lightpack\Cable;

/**
 * Presence Driver Interface
 * 
 * Interface for presence channel drivers that track which users
 * are online in real-time channels.
 */
interface PresenceDriverInterface
{
    /**
     * Join a presence channel
     */
    public function join($userId, string $channel);
    
    /**
     * Leave a presence channel
     */
    public function leave($userId, string $channel);
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat($userId, string $channel);
    
    /**
     * Get users present in a channel
     */
    public function getUsers(string $channel): array;
    
    /**
     * Get channels a user is present in
     */
    public function getChannels($userId): array;
    
    /**
     * Clean up stale presence records
     */
    public function cleanup(): void;
}
