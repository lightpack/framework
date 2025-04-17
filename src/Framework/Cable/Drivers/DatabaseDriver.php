<?php

namespace Lightpack\Cable\Drivers;

use Lightpack\Cable\DriverInterface;
use Lightpack\Database\Database;
use Lightpack\Database\DB;

/**
 * Database Driver for Cable
 * 
 * This driver uses the database to store and retrieve messages.
 */
class DatabaseDriver implements DriverInterface
{
    /**
     * @var Database
     */
    protected $db;
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * Create a new database driver
     */
    public function __construct(DB $db, string $table = 'cable_messages')
    {
        $this->db = $db;
        $this->table = $table;
    }
    
    /**
     * Emit an event to a channel
     */
    public function emit(string $channel, string $event, array $payload): void
    {
        $this->db->table($this->table)->insert([
            'channel' => $channel,
            'event' => $event,
            'payload' => json_encode($payload),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get messages from a channel
     */
    public function getMessages(string $channel, ?int $lastId = null): array
    {
        $query = $this->db->table($this->table)
            ->select('id', 'event', 'payload', 'created_at')
            ->where('channel', $channel);
            
        if ($lastId) {
            $query->where('id', '>', $lastId);
        }
        
        $messages = $query->orderBy('id', 'asc')
            ->limit(100)
            ->all();
            
        // Parse JSON payload
        foreach ($messages as &$message) {
            if (isset($message->payload)) {
                $message->payload = json_decode($message->payload, true);
            }
        }
        
        return $messages;
    }
    
    /**
     * Clean up old messages
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $olderThanSeconds);
        
        $this->db->table($this->table)
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
