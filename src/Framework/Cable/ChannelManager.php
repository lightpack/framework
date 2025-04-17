<?php

namespace Lightpack\Cable;

/**
 * Channel Manager
 * 
 * This class manages channel groups and provides utilities for
 * efficient channel management and grouping.
 */
class ChannelManager
{
    /**
     * @var array
     */
    protected $groups = [];
    
    /**
     * @var Cable
     */
    protected $cable;
    
    /**
     * Create a new channel manager
     */
    public function __construct(Cable $cable)
    {
        $this->cable = $cable;
    }
    
    /**
     * Register a channel group
     */
    public function registerGroup(string $name, array $channels): self
    {
        $this->groups[$name] = $channels;
        return $this;
    }
    
    /**
     * Get channels in a group
     */
    public function getGroup(string $name): array
    {
        if (!isset($this->groups[$name])) {
            throw new \RuntimeException("Channel group not found: {$name}");
        }
        
        return $this->groups[$name];
    }
    
    /**
     * Emit to all channels in a group
     */
    public function emitToGroup(string $name, string $event, array $payload): void
    {
        if (!isset($this->groups[$name])) {
            throw new \RuntimeException("Channel group not found: {$name}");
        }
        
        foreach ($this->groups[$name] as $channel) {
            $this->cable->to($channel)->emit($event, $payload);
        }
    }
    
    /**
     * Get all channels a user should subscribe to
     */
    public function getChannelsForUser(int $userId, array $roles = []): array
    {
        $channels = [
            "user.{$userId}", // User-specific channel
            "broadcasts.all"  // System-wide broadcasts
        ];
        
        // Add role-based channels
        foreach ($roles as $role) {
            $channels[] = "role.{$role}";
        }
        
        return $channels;
    }
    
    /**
     * Batch multiple messages to reduce database writes
     */
    public function batch(string $channel): MessageBatcher
    {
        return new MessageBatcher($this->cable, $channel);
    }
}
