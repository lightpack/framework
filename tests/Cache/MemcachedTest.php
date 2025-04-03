<?php

namespace Lightpack\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Memcached;

class MemcachedTest extends TestCase
{
    private Memcached $memcached;
    
    protected function setUp(): void
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached extension not installed');
        }
        
        $this->memcached = new Memcached();
    }
    
    public function testCanCreateMemcachedInstance()
    {
        $this->assertInstanceOf(\Memcached::class, $this->memcached->getClient());
    }
    
    public function testCanAddServer()
    {
        $this->assertTrue($this->memcached->addServer('127.0.0.1', 11211));
    }
    
    public function testCanGetStats()
    {
        $stats = $this->memcached->getStats();
        $this->assertIsArray($stats);
    }
    
    public function testCanGetVersion()
    {
        $version = $this->memcached->getVersion();
        $this->assertIsArray($version);
    }
}
