<?php

namespace Lightpack\Cable\Drivers;

/**
 * Database Presence Driver
 * 
 * This driver uses a database table to track presence information.
 */
class DatabasePresenceDriver implements PresenceDriverInterface
{
    /**
     * @var \Lightpack\Database\Query
     */
    protected $db;
    
    /**
     * @var string
     */
    protected $table = 'cable_presence';
    
    /**
     * @var int
     */
    protected $timeout = 30; // seconds
    
    /**
     * Create a new database presence driver
     */
    public function __construct($db, $table = 'cable_presence', $timeout = 30)
    {
        $this->db = $db;
        $this->table = $table;
        $this->timeout = $timeout;
    }
    
    /**
     * Set the presence table name
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
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
        // Upsert presence record
        $this->db->table($this->table)->replace([
            'channel' => $channel,
            'user_id' => $userId,
            'last_seen' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Leave a presence channel
     */
    public function leave($userId, string $channel)
    {
        $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->delete();
    }
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat($userId, string $channel)
    {
        $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->update([
                'last_seen' => date('Y-m-d H:i:s'),
            ]);
    }
    
    /**
     * Get users present in a channel
     */
    public function getUsers(string $channel): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);
        
        return $this->db->table($this->table)
            ->select('user_id')
            ->where('channel', $channel)
            ->where('last_seen', '>', $cutoff)
            ->pluck('user_id');
    }
    
    /**
     * Get channels a user is present in
     */
    public function getChannels($userId): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);
        
        return $this->db->table($this->table)
            ->select('channel')
            ->where('user_id', $userId)
            ->where('last_seen', '>', $cutoff)
            ->pluck('channel');
    }
    
    /**
     * Clean up stale presence records
     */
    public function cleanup(): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);
        
        return $this->db->table($this->table)
            ->where('last_seen', '<', $cutoff)
            ->delete();
    }
}
