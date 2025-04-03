<?php

namespace Lightpack\Tests\Cache\Drivers;

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Memcached\Memcached;
use Lightpack\Cache\Drivers\MemcachedDriver;

interface MockMemcached
{
    public function set($key, $value, $expiration);
    public function get($key);
    public function delete($key);
    public function flush();
}

class MemcachedDriverTest extends TestCase
{
    private MemcachedDriver $driver;
    private Memcached $memcached;
    private $client;
    private bool $usingRealMemcached = false;
    
    protected function setUp(): void
    {
        if (extension_loaded('memcached')) {
            $this->usingRealMemcached = true;
            $memcached = new Memcached();
            $this->client = $memcached->getClient();
            $this->memcached = $memcached;
        } else {
            $this->client = $this->createMock(MockMemcached::class);
            $this->memcached = $this->createMock(Memcached::class);
            $this->memcached->method('getClient')
                ->willReturn($this->client);
        }
            
        $this->driver = new MemcachedDriver($this->memcached);
    }
    
    protected function tearDown(): void
    {
        if ($this->usingRealMemcached) {
            $this->client->flush();
        }
    }
    
    public function testCanSetAndGetValue()
    {
        if (!$this->usingRealMemcached) {
            $this->client->expects($this->once())
                ->method('set')
                ->with('test_key', 'test_value', 60)
                ->willReturn(true);
                
            $this->client->expects($this->once())
                ->method('get')
                ->with('test_key')
                ->willReturn('test_value');
        }
            
        $this->driver->set('test_key', 'test_value', 60);
        $this->assertEquals('test_value', $this->driver->get('test_key'));
    }
    
    public function testHasReturnsTrueForExistingKey()
    {
        if (!$this->usingRealMemcached) {
            $this->client->expects($this->once())
                ->method('get')
                ->with('test_key')
                ->willReturn('test_value');
        } else {
            $this->driver->set('test_key', 'test_value', 60);
        }
            
        $this->assertTrue($this->driver->has('test_key'));
    }
    
    public function testHasReturnsFalseForNonExistingKey()
    {
        if (!$this->usingRealMemcached) {
            $this->client->expects($this->once())
                ->method('get')
                ->with('non_existing_key')
                ->willReturn(false);
        }
            
        $this->assertFalse($this->driver->has('non_existing_key'));
    }
    
    public function testCanDeleteKey()
    {
        // First set a value
        $this->driver->set('test_key', 'test_value', 60);
        $this->assertTrue($this->driver->has('test_key'));
        
        // Then delete it
        if (!$this->usingRealMemcached) {
            $this->client->expects($this->once())
                ->method('delete')
                ->with('test_key')
                ->willReturn(true);
                
            $this->client->expects($this->once())
                ->method('get')
                ->with('test_key')
                ->willReturn(false);
        }
            
        $this->assertTrue($this->driver->delete('test_key'));
        $this->assertFalse($this->driver->has('test_key'));
    }
    
    public function testCanFlushCache()
    {
        // First set some values
        $this->driver->set('test_key1', 'test_value1', 60);
        $this->driver->set('test_key2', 'test_value2', 60);
        
        if (!$this->usingRealMemcached) {
            $this->client->expects($this->once())
                ->method('flush')
                ->willReturn(true);
                
            $this->client->expects($this->exactly(2))
                ->method('get')
                ->willReturn(false);
        }
            
        $this->assertTrue($this->driver->flush());
        $this->assertFalse($this->driver->has('test_key1'));
        $this->assertFalse($this->driver->has('test_key2'));
    }
    
    /**
     * @requires extension memcached
     */
    public function testValueExpiresAfterTtl()
    {
        $this->driver->set('test_key', 'test_value', 1);
        $this->assertTrue($this->driver->has('test_key'));
        
        sleep(2);
        $this->assertFalse($this->driver->has('test_key'));
    }
    
    /**
     * @requires extension memcached
     */
    public function testCanHandleLargeValues()
    {
        $largeValue = str_repeat('x', 1024 * 1024); // 1MB
        $this->driver->set('large_key', $largeValue, 60);
        $this->assertEquals($largeValue, $this->driver->get('large_key'));
    }
    
    /**
     * @requires extension memcached
     */
    public function testCanHandleSpecialCharacters()
    {
        $value = '!@#$%^&*()_+-=[]{}|;:,.<>?/~`';
        $this->driver->set('special_key', $value, 60);
        $this->assertEquals($value, $this->driver->get('special_key'));
    }
}
