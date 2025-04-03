<?php

namespace Lightpack\Tests\Session\Drivers;

use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Http\Cookie;
use Lightpack\Session\Drivers\CacheDriver;
use PHPUnit\Framework\TestCase;

/**
 * @note IMPORTANT: Run these tests with --stderr option to prevent PHPUnit output from sending headers
 * Example: vendor/bin/phpunit --stderr tests/Session/Drivers/CacheDriverTest.php
 */
class CacheDriverTest extends TestCase
{
    private CacheDriver $driver;
    private Cache $cache;
    private $cookie;
    private array $cookieData = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent - cannot test session functionality');
        }

        // Set session name
        session_name('test_session');

        $this->cache = new Cache(new ArrayDriver());
        
        // Mock cookie operations
        $this->cookie = $this->createMock(Cookie::class);
        $this->cookie->method('get')
            ->willReturnCallback(fn($key) => $this->cookieData[$key] ?? null);
        $this->cookie->method('set')
            ->willReturnCallback(function($key, $value) {
                $this->cookieData[$key] = $value;
                return true;
            });
        $this->cookie->method('delete')
            ->willReturnCallback(function($key) {
                unset($this->cookieData[$key]);
                return true;
            });

        $this->driver = new CacheDriver($this->cache, $this->cookie);
    }

    public function testStartCreatesNewSession()
    {
        $this->driver->start();
        $this->assertTrue($this->driver->started());
    }

    public function testSetAndGetData()
    {
        $this->driver->start();
        $this->driver->set('key', 'value');
        $this->assertEquals('value', $this->driver->get('key'));
    }

    public function testGetAllData()
    {
        $this->driver->start();
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');

        $data = $this->driver->get();
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $data);
    }

    public function testDeleteData()
    {
        $this->driver->start();
        $this->driver->set('key', 'value');
        $this->driver->delete('key');
        $this->assertNull($this->driver->get('key'));
    }

    public function testRegenerateSession()
    {
        $this->driver->start();
        $this->driver->set('key', 'value');
        
        $oldSessionId = $this->getSessionId();
        $this->driver->regenerate();
        $newSessionId = $this->getSessionId();

        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertEquals('value', $this->driver->get('key'));
    }

    public function testDestroy()
    {
        $this->driver->start();
        $this->driver->set('key', 'value');
        $this->driver->destroy();

        $this->assertFalse($this->driver->started());
        $this->assertEmpty($this->driver->get());
    }

    public function testGetDefaultValue()
    {
        $this->driver->start();
        $this->assertEquals('default', $this->driver->get('nonexistent', 'default'));
    }

    public function testSessionPersistence()
    {
        // First session
        $this->driver->start();
        $this->driver->set('key', 'value');
        $sessionId = $this->getSessionId();
        
        // Simulate new request
        $this->cookieData[session_name()] = $sessionId;
        
        $newDriver = new CacheDriver($this->cache, $this->cookie);
        $newDriver->start();
        $this->assertEquals('value', $newDriver->get('key'));
    }

    private function getSessionId()
    {
        $reflection = new \ReflectionClass($this->driver);
        $property = $reflection->getProperty('sessionId');
        $property->setAccessible(true);
        return $property->getValue($this->driver);
    }
}
