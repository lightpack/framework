<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Redis\Redis;
use Lightpack\Cache\Drivers\RedisDriver;

final class RedisDriverTest extends TestCase
{
    private $redis;
    private $driver;
    private $prefix = 'test_cache:';
    
    public function setUp(): void
    {
        // Skip tests if Redis extension is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not available');
        }
        
        try {
            $this->redis = new Redis([
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 15, // Use database 15 for testing
            ]);
            
            // Test connection
            $this->redis->connect();
            
            // Create driver with test prefix
            $this->driver = new RedisDriver($this->redis, $this->prefix);
            
            // Flush test database before each test
            $this->redis->flush();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server is not available: ' . $e->getMessage());
        }
    }
    
    public function tearDown(): void
    {
        if ($this->redis) {
            // Clean up after tests
            $this->redis->flush();
        }
    }
    
    public function testCanStoreItem()
    {
        $this->driver->set('name', 'Lightpack', time() + 3600);
        
        $this->assertTrue($this->driver->has('name'));
        $this->assertEquals('Lightpack', $this->driver->get('name'));
    }
    
    public function testCanDeleteItem()
    {
        $this->driver->set('name', 'Lightpack', time() + 3600);
        
        $this->assertTrue($this->driver->has('name'));
        $this->driver->delete('name');
        $this->assertFalse($this->driver->has('name'));
    }
    
    public function testCanDeleteMultipleItems()
    {
        $this->driver->set('key1', 'value1', time() + 3600);
        $this->driver->set('key2', 'value2', time() + 3600);
        
        $this->assertTrue($this->driver->has('key1'));
        $this->assertTrue($this->driver->has('key2'));
        
        $this->driver->delete(['key1', 'key2']);
        
        $this->assertFalse($this->driver->has('key1'));
        $this->assertFalse($this->driver->has('key2'));
    }
    
    public function testCanFlushItems()
    {
        $this->driver->set('key1', 'value1', time() + 3600);
        $this->driver->set('key2', 'value2', time() + 3600);
        
        $this->assertTrue($this->driver->has('key1'));
        $this->assertTrue($this->driver->has('key2'));
        
        $this->driver->flush();
        
        $this->assertFalse($this->driver->has('key1'));
        $this->assertFalse($this->driver->has('key2'));
    }
    
    public function testCanStoreComplexData()
    {
        $data = [
            'name' => 'Lightpack',
            'version' => 2.0,
            'features' => ['cache', 'session', 'redis'],
            'active' => true,
        ];
        
        $this->driver->set('framework', $data, time() + 3600);
        
        $this->assertTrue($this->driver->has('framework'));
        $this->assertEquals($data, $this->driver->get('framework'));
    }
    
    public function testItemExpiresAfterTtl()
    {
        // Set item with 1 second TTL
        $this->driver->set('expires', 'soon', time() + 1);
        
        $this->assertTrue($this->driver->has('expires'));
        
        // Sleep for 2 seconds to allow expiration
        sleep(2);
        
        $this->assertFalse($this->driver->has('expires'));
        $this->assertNull($this->driver->get('expires'));
    }
    
    public function testPreserveTtl()
    {
        // Set item with 10 second TTL
        $this->driver->set('preserve', 'original', time() + 10);
        
        // Update value but preserve TTL
        $this->driver->set('preserve', 'updated', 0, true);
        
        $this->assertEquals('updated', $this->driver->get('preserve'));
        
        // TTL should still be close to 10 seconds
        $ttl = $this->redis->ttl($this->prefix . 'preserve');
        $this->assertGreaterThan(5, $ttl);
    }
}
