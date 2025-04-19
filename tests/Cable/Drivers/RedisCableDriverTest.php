<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Cable\Drivers\RedisDriver;
use Lightpack\Redis\Redis;

final class RedisCableDriverTest extends TestCase
{
    private $redis;
    private $driver;
    private $prefix = 'test_cable:';

    public function setUp(): void
    {
        // Skip tests if Redis extension is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not available');
        }
        
        try {
            // Create Redis client
            $redisClient = new Redis();
            
            // Create driver with test prefix
            $this->driver = new RedisDriver($redisClient, $this->prefix);
            
            // Get native Redis connection for test verification
            $this->redis = $redisClient->connection();
            
            // Flush test database before each test
            $this->redis->select(15); // Use database 15 for testing
            $this->redis->flushDB();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server is not available: ' . $e->getMessage());
        }
    }

    public function tearDown(): void
    {
        if ($this->redis) {
            // Clean up after tests
            $this->redis->flushDB();
        }
    }

    public function testEmit(): void
    {
        // Emit an event
        $this->driver->emit('test-channel', 'test-event', ['message' => 'Hello, world!']);
        
        // Verify the event was stored in Redis
        $channelKey = $this->prefix . 'test-channel';
        $messages = $this->redis->zRange($channelKey, 0, -1);
        
        $this->assertCount(1, $messages);
        
        $data = json_decode($messages[0], true);
        $this->assertEquals('test-event', $data['event']);
        $this->assertEquals(['message' => 'Hello, world!'], $data['payload']);
    }
    
    public function testGetMessages(): void
    {
        // Add a test message directly to Redis
        $channelKey = $this->prefix . 'test-channel';
        $message = json_encode([
            'id' => microtime(true),
            'event' => 'test-event',
            'payload' => ['message' => 'Hello, world!'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->redis->zAdd($channelKey, microtime(true), $message);
        
        // Get messages
        $events = $this->driver->getMessages('test-channel');
        
        // Verify the event was retrieved
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->event);
        $this->assertEquals(['message' => 'Hello, world!'], $events[0]->payload);
    }
    
    public function testGetMessagesWithLastId(): void
    {
        // Add two test messages with different timestamps
        $channelKey = $this->prefix . 'test-channel';
        
        $timestamp1 = (int)(microtime(true) * 1000); // Convert to milliseconds integer
        $message1 = json_encode([
            'id' => $timestamp1,
            'event' => 'event-1',
            'payload' => ['message' => 'First message'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Make sure timestamp2 is greater than timestamp1
        $timestamp2 = $timestamp1 + 100; // 100 milliseconds later
        $message2 = json_encode([
            'id' => $timestamp2,
            'event' => 'event-2',
            'payload' => ['message' => 'Second message'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->redis->zAdd($channelKey, $timestamp1, $message1);
        $this->redis->zAdd($channelKey, $timestamp2, $message2);
        
        // Test 1: Get all messages (no lastId)
        $events = $this->driver->getMessages('test-channel');
        $this->assertCount(2, $events);
        $this->assertEquals('event-1', $events[0]->event);
        $this->assertEquals('event-2', $events[1]->event);
        
        // Test 2: Get messages with lastId = timestamp1
        // This should return only the second message (exclusive range)
        $events = $this->driver->getMessages('test-channel', $timestamp1);
        $this->assertCount(1, $events);
        $this->assertEquals('event-2', $events[0]->event);
        
        // Test 3: Get messages with lastId = timestamp2
        // This should return no messages
        $events = $this->driver->getMessages('test-channel', $timestamp2);
        $this->assertCount(0, $events);
    }
    
    public function testCleanup(): void
    {
        // Add some old messages
        $channelKey = $this->prefix . 'test-channel';
        $oldTimestamp = microtime(true) - 86400; // 1 day old
        
        for ($i = 0; $i < 5; $i++) {
            $message = json_encode([
                'id' => $oldTimestamp + $i,
                'event' => "old-event-$i",
                'payload' => ['message' => "Old message $i"],
                'created_at' => date('Y-m-d H:i:s', time() - 86400)
            ]);
            
            $this->redis->zAdd($channelKey, $oldTimestamp + $i, $message);
        }
        
        // Add some recent messages
        $recentTimestamp = microtime(true);
        
        for ($i = 0; $i < 3; $i++) {
            $message = json_encode([
                'id' => $recentTimestamp + $i,
                'event' => "recent-event-$i",
                'payload' => ['message' => "Recent message $i"],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->redis->zAdd($channelKey, $recentTimestamp + $i, $message);
        }
        
        // Verify we have 8 messages total
        $this->assertEquals(8, $this->redis->zCard($channelKey));
        
        // Run cleanup with 12 hour TTL
        $ttl = 43200; // 12 hours in seconds
        $this->driver->cleanup($ttl);
        
        // Verify the old messages were removed
        $remaining = $this->redis->zCard($channelKey);
        $this->assertEquals(3, $remaining);
        
        // Verify only recent messages remain
        $messages = $this->redis->zRange($channelKey, 0, -1);
        foreach ($messages as $message) {
            $data = json_decode($message, true);
            $this->assertStringContainsString('recent-event', $data['event']);
        }
    }
}
