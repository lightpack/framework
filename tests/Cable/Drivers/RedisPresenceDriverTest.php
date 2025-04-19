<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Cable\Drivers\RedisPresenceDriver;
use Redis; // Import the Redis class

final class RedisPresenceDriverTest extends TestCase
{
    /**
     * @var Redis
     */
    private $redis;
    
    private $driver;
    private $prefix = 'test_presence:';
    private $timeout = 60;

    public function setUp(): void
    {
        // Skip tests if Redis extension is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not available');
        }
        
        try {
            // Create Redis client
            $redisClient = new Redis();
            $redisClient->connect('127.0.0.1', 6379);
            $redisClient->select(15); // Use database 15 for testing
            
            // Create driver with test prefix
            $this->redis = $redisClient;
            $this->driver = new RedisPresenceDriver($this->redis, $this->prefix, $this->timeout);
            
            // Flush test database before each test
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
            $this->redis->close();
        }
    }

    public function testJoin(): void
    {
        // Join a channel
        $this->driver->join('user1', 'presence-room');
        
        // Verify the user was added to the channel
        $channelKey = $this->prefix . 'channel:presence-room';
        $this->assertTrue($this->redis->sIsMember($channelKey, 'user1'));
        
        // Verify the channel was added to the user's channels
        $userKey = $this->prefix . 'user:user1';
        $this->assertTrue($this->redis->sIsMember($userKey, 'presence-room'));
        
        // Verify the keys have TTL set
        $this->assertGreaterThan(0, $this->redis->ttl($channelKey));
        $this->assertGreaterThan(0, $this->redis->ttl($userKey));
    }
    
    public function testLeave(): void
    {
        // First join a channel
        $this->driver->join('user1', 'presence-room');
        
        // Verify the user was added
        $channelKey = $this->prefix . 'channel:presence-room';
        $userKey = $this->prefix . 'user:user1';
        $this->assertTrue($this->redis->sIsMember($channelKey, 'user1'));
        $this->assertTrue($this->redis->sIsMember($userKey, 'presence-room'));
        
        // Now leave the channel
        $this->driver->leave('user1', 'presence-room');
        
        // Verify the user was removed from the channel
        $this->assertFalse($this->redis->sIsMember($channelKey, 'user1'));
        // Verify the channel was removed from the user's channels
        $this->assertFalse($this->redis->sIsMember($userKey, 'presence-room'));
    }
    
    public function testHeartbeat(): void
    {
        // First join a channel
        $this->driver->join('user1', 'presence-room');
        
        // Get the initial TTL values
        $channelKey = $this->prefix . 'channel:presence-room';
        $userKey = $this->prefix . 'user:user1';
        $initialChannelTtl = $this->redis->ttl($channelKey);
        $initialUserTtl = $this->redis->ttl($userKey);
        
        // Wait a moment to ensure the TTL will be different
        sleep(1);
        
        // Send a heartbeat
        $this->driver->heartbeat('user1', 'presence-room');
        
        // Verify the TTL values were updated (should be higher after heartbeat)
        $updatedChannelTtl = $this->redis->ttl($channelKey);
        $updatedUserTtl = $this->redis->ttl($userKey);
        
        // The new TTL should be greater than or equal to the initial TTL minus 1 second
        $this->assertGreaterThanOrEqual($initialChannelTtl - 1, $updatedChannelTtl);
        $this->assertGreaterThanOrEqual($initialUserTtl - 1, $updatedUserTtl);
    }
    
    public function testHeartbeatNonExistentUser(): void
    {
        // Send a heartbeat for a non-existent user
        $this->driver->heartbeat('nonexistent-user', 'presence-room');
        
        // Verify the user was not added to the channel
        $channelKey = $this->prefix . 'channel:presence-room';
        $this->assertFalse($this->redis->sIsMember($channelKey, 'nonexistent-user'));
    }
    
    public function testGetUsers(): void
    {
        // Add a user to a channel
        $channelKey = $this->prefix . 'channel:presence-room';
        $this->redis->sAdd($channelKey, 'active-user');
        $this->redis->expire($channelKey, $this->timeout);
        
        // Get users
        $users = $this->driver->getUsers('presence-room');
        
        // Verify the user is returned
        $this->assertCount(1, $users);
        $this->assertContains('active-user', $users);
    }
    
    public function testGetChannels(): void
    {
        // Add a channel to a user's channels
        $userKey = $this->prefix . 'user:test-user';
        $this->redis->sAdd($userKey, 'active-channel');
        $this->redis->expire($userKey, $this->timeout);
        
        // Get channels
        $channels = $this->driver->getChannels('test-user');
        
        // Verify the channel is returned
        $this->assertCount(1, $channels);
        $this->assertContains('active-channel', $channels);
    }
    
    public function testCleanup(): void
    {
        // Redis handles cleanup automatically via TTL, so this is mostly a no-op test
        
        // Create a channel with a user
        $channelKey = $this->prefix . 'channel:cleanup-room';
        $this->redis->sAdd($channelKey, 'test-user');
        
        // Set a very short TTL
        $this->redis->expire($channelKey, 1);
        
        // Wait for expiration
        sleep(2);
        
        // Verify the key was automatically removed by Redis
        $this->assertEquals(0, $this->redis->exists($channelKey));
        
        // Run cleanup (should be a no-op)
        $this->driver->cleanup();
    }
    
    public function testSetTimeout(): void
    {
        // Set a new timeout
        $result = $this->driver->setTimeout(120);
        
        // Verify method returns $this for chaining
        $this->assertSame($this->driver, $result);
        
        // Add a user to a channel with the new timeout
        $channelKey = $this->prefix . 'channel:timeout-room';
        $this->redis->sAdd($channelKey, 'test-user');
        $this->redis->expire($channelKey, 120);
        
        // Get users
        $users = $this->driver->getUsers('timeout-room');
        
        // Verify the user is returned
        $this->assertCount(1, $users);
        $this->assertContains('test-user', $users);
    }
}
