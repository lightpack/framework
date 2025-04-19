<?php

declare(strict_types=1);

use Lightpack\Redis\Redis;
use PHPUnit\Framework\TestCase;

final class RedisTest extends TestCase
{
    private $redis;
    
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
                'prefix' => 'test:', // Use a prefix for test keys
            ]);
            
            // Test connection
            $this->redis->connect();
            
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
    
    public function testSetAndGet()
    {
        $this->redis->set('key', 'value');
        $this->assertEquals('value', $this->redis->get('key'));
    }
    
    public function testSetWithTtl()
    {
        $this->redis->set('key', 'value', 1);
        $this->assertEquals('value', $this->redis->get('key'));
        
        // Sleep for 2 seconds to allow the key to expire
        sleep(2);
        
        $this->assertNull($this->redis->get('key'));
    }
    
    public function testExists()
    {
        $this->redis->set('key', 'value');
        $this->assertTrue($this->redis->exists('key'));
        $this->assertFalse($this->redis->exists('nonexistent'));
    }
    
    public function testDelete()
    {
        $this->redis->set('key', 'value');
        $this->assertTrue($this->redis->exists('key'));
        
        $this->redis->delete('key');
        $this->assertFalse($this->redis->exists('key'));
    }
    
    public function testDeleteMultiple()
    {
        $this->redis->set('key1', 'value1');
        $this->redis->set('key2', 'value2');
        
        $this->redis->deleteMultiple(['key1', 'key2']);
        
        $this->assertFalse($this->redis->exists('key1'));
        $this->assertFalse($this->redis->exists('key2'));
    }
    
    public function testIncrement()
    {
        $this->redis->set('counter', 5);
        $this->assertEquals(6, $this->redis->increment('counter'));
        $this->assertEquals(8, $this->redis->increment('counter', 2));
    }
    
    public function testDecrement()
    {
        $this->redis->set('counter', 5);
        $this->assertEquals(4, $this->redis->decrement('counter'));
        $this->assertEquals(2, $this->redis->decrement('counter', 2));
    }
    
    public function testExpire()
    {
        $this->redis->set('key', 'value');
        $this->redis->expire('key', 1);
        
        $this->assertTrue($this->redis->exists('key'));
        
        // Sleep for 2 seconds to allow the key to expire
        sleep(2);
        
        $this->assertFalse($this->redis->exists('key'));
    }
    
    public function testTtl()
    {
        $this->redis->set('key', 'value', 10);
        
        $ttl = $this->redis->ttl('key');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(10, $ttl);
    }
    
    public function testFlush()
    {
        $this->redis->set('key1', 'value1');
        $this->redis->set('key2', 'value2');
        
        $this->redis->flush();
        
        $this->assertFalse($this->redis->exists('key1'));
        $this->assertFalse($this->redis->exists('key2'));
    }
    
    public function testKeys()
    {
        $this->redis->set('key1', 'value1');
        $this->redis->set('key2', 'value2');
        
        $keys = $this->redis->keys('*key*');
        
        $this->assertCount(2, $keys);
        // The keys will have the test: prefix from the Redis constructor
        $this->assertContains('test:key1', $keys);
        $this->assertContains('test:key2', $keys);
    }
    
    public function testSerializeAndUnserialize()
    {
        $data = ['name' => 'John', 'age' => 30];
        
        $this->redis->set('user', $data);
        $result = $this->redis->get('user');
        
        $this->assertEquals($data, $result);
    }
    
    public function testMagicCall()
    {
        $this->redis->set('key', 'value');
        $result = $this->redis->get('key');
        
        $this->assertEquals('value', $result);
    }
}
