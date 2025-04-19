<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Cable\Drivers\DatabasePresenceDriver;

final class DatabasePresenceDriverTest extends TestCase
{
    private $db;
    private $driver;
    private $table = 'cable_presence_test';
    private $timeout = 60; // 60 seconds timeout

    public function setUp(): void
    {        
        try {
            $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
            $this->db = new Mysql($config);
            
            // Create test table
            $this->db->query("DROP TABLE IF EXISTS {$this->table}");
            $this->db->query("
                CREATE TABLE {$this->table} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    channel VARCHAR(255) NOT NULL,
                    user_id VARCHAR(255) NOT NULL,
                    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (channel),
                    INDEX (user_id),
                    UNIQUE KEY channel_user (channel, user_id)
                )
            ");
            
            // Initialize the driver with our test table
            $this->driver = new DatabasePresenceDriver($this->db, $this->table, $this->timeout);
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

    public function testJoin(): void
    {
        $userId = '123';
        $channel = 'presence-room';
        
        // Join the channel
        $this->driver->join($userId, $channel);
        
        // Verify user was added to the channel
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
        
        $this->assertNotNull($result);
        $this->assertEquals($channel, $result->channel);
        $this->assertEquals($userId, $result->user_id);
    }
    
    public function testJoinTwice(): void
    {
        $userId = '123';
        $channel = 'presence-room';
        
        // Join the channel twice
        $this->driver->join($userId, $channel);
        
        // Get the first last_seen timestamp
        $firstJoin = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
            
        // Wait a moment to ensure timestamp changes
        sleep(1);
        
        // Join again
        $this->driver->join($userId, $channel);
        
        // Get the updated last_seen timestamp
        $secondJoin = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
        
        // Verify only one record exists but last_seen was updated
        $count = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->count();
            
        $this->assertEquals(1, $count);
        $this->assertGreaterThan($firstJoin->last_seen, $secondJoin->last_seen);
    }
    
    public function testLeave(): void
    {
        $userId = '123';
        $channel = 'presence-room';
        
        // Join then leave the channel
        $this->driver->join($userId, $channel);
        $this->driver->leave($userId, $channel);
        
        // Verify user was removed from the channel
        $result = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
        
        $this->assertNull($result);
    }
    
    public function testLeaveNonExistentUser(): void
    {
        $userId = 'non-existent';
        $channel = 'presence-room';
        
        // Leave without joining first
        $this->driver->leave($userId, $channel);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    public function testHeartbeat(): void
    {
        $userId = '123';
        $channel = 'presence-room';
        
        // Join the channel
        $this->driver->join($userId, $channel);
        
        // Get the first last_seen timestamp
        $beforeHeartbeat = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
            
        // Wait a moment to ensure timestamp changes
        sleep(1);
        
        // Send heartbeat
        $this->driver->heartbeat($userId, $channel);
        
        // Get the updated last_seen timestamp
        $afterHeartbeat = $this->db->table($this->table)
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->one();
        
        $this->assertGreaterThan($beforeHeartbeat->last_seen, $afterHeartbeat->last_seen);
    }
    
    public function testGetUsers(): void
    {
        $channel = 'presence-room';
        
        // Add multiple users
        $this->driver->join('user1', $channel);
        $this->driver->join('user2', $channel);
        $this->driver->join('user3', $channel);
        
        // Get users in the channel
        $users = $this->driver->getUsers($channel);
        
        $this->assertCount(3, $users);
        $this->assertContains('user1', $users);
        $this->assertContains('user2', $users);
        $this->assertContains('user3', $users);
    }
    
    public function testGetUsersWithTimeout(): void
    {
        $channel = 'presence-room';
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);

        // Add users
        $this->driver->join('active-user', $channel);
        $this->driver->join('inactive-user', $channel);

        // Make inactive user's last_seen older than the timeout
        $this->db->query("
            UPDATE {$this->table} 
            SET last_seen = '{$cutoff}'
            WHERE user_id = 'inactive-user'
        ");
        
        // Get active users
        $users = $this->driver->getUsers($channel);
        
        $this->assertCount(1, $users);
        $this->assertContains('active-user', $users);
        $this->assertNotContains('inactive-user', $users);
    }
    
    public function testGetUsersEmptyChannel(): void
    {
        $channel = 'empty-room';
        
        // Get users from an empty channel
        $users = $this->driver->getUsers($channel);
        
        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }
    
    public function testGetChannels(): void
    {
        $userId = 'multi-channel-user';
        
        // Join multiple channels
        $this->driver->join($userId, 'channel1');
        $this->driver->join($userId, 'channel2');
        $this->driver->join($userId, 'channel3');
        
        // Get channels for the user
        $channels = $this->driver->getChannels($userId);
        
        $this->assertCount(3, $channels);
        $this->assertContains('channel1', $channels);
        $this->assertContains('channel2', $channels);
        $this->assertContains('channel3', $channels);
    }
    
    public function testGetChannelsWithTimeout(): void
    {
        $userId = 'timeout-test-user';
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);
        
        // Join channels
        $this->driver->join($userId, 'active-channel');
        $this->driver->join($userId, 'inactive-channel');
        
        // Make inactive channel's last_seen older than the timeout
        $this->db->query("
            UPDATE {$this->table} 
            SET last_seen = '{$cutoff}'
            WHERE channel = 'inactive-channel'
        ");
        
        // Get active channels
        $channels = $this->driver->getChannels($userId);
        
        $this->assertCount(1, $channels);
        $this->assertContains('active-channel', $channels);
        $this->assertNotContains('inactive-channel', $channels);
    }
    
    public function testGetChannelsNoChannels(): void
    {
        $userId = 'no-channels-user';
        
        // Get channels for a user who hasn't joined any
        $channels = $this->driver->getChannels($userId);
        
        $this->assertIsArray($channels);
        $this->assertEmpty($channels);
    }
    
    public function testCleanup(): void
    {
        $channel = 'cleanup-test-channel';
        $cutoff = date('Y-m-d H:i:s', time() - $this->timeout);
        
        // Add active and inactive users
        $this->driver->join('active-user', $channel);
        $this->driver->join('inactive-user', $channel);
        
        // Make inactive user's last_seen older than the timeout
        $this->db->query("
            UPDATE {$this->table} 
            SET last_seen = '{$cutoff}'
            WHERE user_id = 'inactive-user'
        ");
        
        // Run cleanup
        $this->driver->cleanup();
        
        // Check that only active user remains
        $users = $this->driver->getUsers($channel);
        
        $this->assertCount(1, $users);
        $this->assertContains('active-user', $users);
        $this->assertNotContains('inactive-user', $users);
    }
    
    public function testMultipleChannelsAndUsers(): void
    {
        // Create a complex presence scenario
        $this->driver->join('user1', 'channel1');
        $this->driver->join('user1', 'channel2');
        $this->driver->join('user2', 'channel1');
        $this->driver->join('user3', 'channel2');
        $this->driver->join('user4', 'channel3');
        
        // Test getUsers for each channel
        $this->assertEquals(['user1', 'user2'], $this->driver->getUsers('channel1'));
        $this->assertEquals(['user1', 'user3'], $this->driver->getUsers('channel2'));
        $this->assertEquals(['user4'], $this->driver->getUsers('channel3'));
        
        // Test getChannels for each user
        $this->assertEquals(['channel1', 'channel2'], $this->driver->getChannels('user1'));
        $this->assertEquals(['channel1'], $this->driver->getChannels('user2'));
        $this->assertEquals(['channel2'], $this->driver->getChannels('user3'));
        $this->assertEquals(['channel3'], $this->driver->getChannels('user4'));
        
        // Remove a user from one channel
        $this->driver->leave('user1', 'channel1');
        
        // Verify user was removed only from that channel
        $this->assertEquals(['user2'], $this->driver->getUsers('channel1'));
        $this->assertEquals(['user1', 'user3'], $this->driver->getUsers('channel2'));
        $this->assertEquals(['channel2'], $this->driver->getChannels('user1'));
    }
}
