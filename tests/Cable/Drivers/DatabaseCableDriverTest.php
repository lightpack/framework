<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Cable\Drivers\DatabaseCableDriver;

final class DatabaseCableDriverTest extends TestCase
{
    private $db;
    private $driver;
    private $table = 'cable_messages_test';

    public function setUp(): void
    {
        try {
            // Use the same database config as other tests
            $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
            $this->db = new Mysql($config);
            
            // Create test table
            $this->db->query("DROP TABLE IF EXISTS {$this->table}");
            $this->db->query("
                CREATE TABLE {$this->table} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    channel VARCHAR(255) NOT NULL,
                    event VARCHAR(255) NOT NULL,
                    payload TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Initialize the driver with our test table
            $this->driver = new DatabaseCableDriver($this->db, $this->table);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to database: ' . $e->getMessage());
        }
    }

    public function tearDown(): void
    {
        if ($this->db) {
            $this->db->query("DROP TABLE IF EXISTS {$this->table}");
            $this->db = null;
        }
        $this->driver = null;
    }

    public function testEmit(): void
    {
        $channel = 'test-channel';
        $event = 'test-event';
        $payload = ['message' => 'Hello, world!', 'timestamp' => time()];
        
        // Emit a message
        $this->driver->emit($channel, $event, $payload);
        
        // Verify message was stored
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('event', $event)
            ->one();
        
        $this->assertNotNull($result);
        $this->assertEquals($channel, $result->channel);
        $this->assertEquals($event, $result->event);
        
        // Verify payload was stored as JSON
        $storedPayload = json_decode($result->payload, true);
        $this->assertEquals($payload, $storedPayload);
    }
    
    public function testGetMessages(): void
    {
        $channel = 'test-channel';
        
        // Insert test messages
        $this->driver->emit($channel, 'event-1', ['message' => 'Message 1']);
        $this->driver->emit($channel, 'event-2', ['message' => 'Message 2']);
        $this->driver->emit($channel, 'event-3', ['message' => 'Message 3']);
        
        // Get all messages
        $messages = $this->driver->getMessages($channel);
        
        $this->assertCount(3, $messages);
        $this->assertEquals('event-1', $messages[0]->event);
        $this->assertEquals('event-2', $messages[1]->event);
        $this->assertEquals('event-3', $messages[2]->event);
    }
    
    public function testGetMessagesWithLastId(): void
    {
        $channel = 'test-channel';
        
        // Insert test messages
        $this->driver->emit($channel, 'event-1', ['message' => 'Message 1']);
        $this->driver->emit($channel, 'event-2', ['message' => 'Message 2']);
        $this->driver->emit($channel, 'event-3', ['message' => 'Message 3']);
        
        // Get first message to get its ID
        $firstMessage = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('event', 'event-1')
            ->one();
        
        // Get messages after the first one
        $messages = $this->driver->getMessages($channel, $firstMessage->id);
        
        $this->assertCount(2, $messages);
        $this->assertEquals('event-2', $messages[0]->event);
        $this->assertEquals('event-3', $messages[1]->event);
    }
    
    public function testGetMessagesFromDifferentChannels(): void
    {
        // Insert test messages in different channels
        $this->driver->emit('channel-1', 'event-1', ['message' => 'Channel 1 Message']);
        $this->driver->emit('channel-2', 'event-1', ['message' => 'Channel 2 Message']);
        
        // Get messages from channel 1
        $messages1 = $this->driver->getMessages('channel-1');
        $this->assertCount(1, $messages1);
        $this->assertEquals('Channel 1 Message', $messages1[0]->payload['message']);
        
        // Get messages from channel 2
        $messages2 = $this->driver->getMessages('channel-2');
        $this->assertCount(1, $messages2);
        $this->assertEquals('Channel 2 Message', $messages2[0]->payload['message']);
    }
    
    public function testCleanup(): void
    {
        $channel = 'test-channel';
        
        // Insert test messages
        $this->driver->emit($channel, 'event-1', ['message' => 'Message 1']);
        
        // Manually update the created_at to be older
        $this->db->query("
            UPDATE {$this->table} 
            SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY)
            WHERE event = 'event-1'
        ");
        
        // Add a newer message
        $this->driver->emit($channel, 'event-2', ['message' => 'Message 2']);
        
        // Cleanup messages older than 1 day
        $this->driver->cleanup(86400); // 1 day in seconds
        
        // Check that only the newer message remains
        $messages = $this->driver->getMessages($channel);
        
        $this->assertCount(1, $messages);
        $this->assertEquals('event-2', $messages[0]->event);
    }
    
    public function testEmitWithSpecialCharacters(): void
    {
        $channel = 'test-channel';
        $event = 'test-event';
        $payload = ['message' => 'Special chars: !@#$%^&*()_+{}:"<>?[];\',./'];
        
        // Emit a message with special characters
        $this->driver->emit($channel, $event, $payload);
        
        // Verify message was stored correctly
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('event', $event)
            ->one();
        
        $storedPayload = json_decode($result->payload, true);
        $this->assertEquals($payload['message'], $storedPayload['message']);
    }
    
    public function testEmitWithEmptyPayload(): void
    {
        $channel = 'test-channel';
        $event = 'test-event';
        $payload = [];
        
        // Emit a message with empty payload
        $this->driver->emit($channel, $event, $payload);
        
        // Verify message was stored
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('event', $event)
            ->one();
        
        $this->assertNotNull($result);
        $storedPayload = json_decode($result->payload, true);
        $this->assertEquals($payload, $storedPayload);
    }
    
    public function testEmitWithNestedPayload(): void
    {
        $channel = 'test-channel';
        $event = 'test-event';
        $payload = [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true
                ]
            ],
            'message' => 'Hello, world!'
        ];
        
        // Emit a message with nested payload
        $this->driver->emit($channel, $event, $payload);
        
        // Verify message was stored correctly
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('event', $event)
            ->one();
        
        $storedPayload = json_decode($result->payload, true);
        $this->assertEquals($payload, $storedPayload);
        $this->assertEquals('John Doe', $storedPayload['user']['name']);
        $this->assertEquals('dark', $storedPayload['user']['preferences']['theme']);
    }
    
    public function testGetMessagesWithNoResults(): void
    {
        // Get messages from an empty channel
        $messages = $this->driver->getMessages('empty-channel');
        
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
    
    public function testGetMessagesWithNonExistentLastId(): void
    {
        $channel = 'test-channel';
        
        // Insert test message
        $this->driver->emit($channel, 'event-1', ['message' => 'Message 1']);
        
        // Try to get messages with a non-existent last ID
        $messages = $this->driver->getMessages($channel, 9999);
        
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
    
    public function testCleanupWithNoOldMessages(): void
    {
        $channel = 'test-channel';
        
        // Insert test message
        $this->driver->emit($channel, 'event-1', ['message' => 'Message 1']);
        
        // Run cleanup (all messages are new)
        $this->driver->cleanup(86400);
        
        // Verify message still exists
        $messages = $this->driver->getMessages($channel);
        $this->assertCount(1, $messages);
    }
}
