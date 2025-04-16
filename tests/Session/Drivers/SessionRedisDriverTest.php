<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Redis\Redis;
use Lightpack\Session\Drivers\RedisDriver;

/**
 * @note IMPORTANT: Run these tests with --stderr option to prevent PHPUnit output from sending headers
 * Example: vendor/bin/phpunit --stderr tests/Session/Drivers/RedisDriverTest.php
 * 
 * This is necessary because:
 * 1. PHPUnit's output normally goes to stdout, which sends headers
 * 2. Sessions must be started before any headers are sent
 * 3. Using --stderr prevents PHPUnit's output from triggering headers
 */
class SessionRedisDriverTestSessionRedisDriverTest extends TestCase
{
    private $redis;
    private $driver;
    private $sessionName = 'test_session';
    private $prefix = 'test_session:';
    private $lifetime = 3600;
    
    protected function setUp(): void
    {
        // Skip tests if Redis extension is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not available');
        }
        
        // Clean any previous session and output
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent - cannot test session functionality');
        }
        
        // Set up test environment
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Browser';
        
        try {
            $this->redis = new Redis([
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 15, // Use database 15 for testing
            ]);
            
            // Test connection
            $this->redis->connect();
            
            // Create driver with test settings
            $this->driver = new RedisDriver(
                $this->redis, 
                $this->sessionName, 
                $this->lifetime, 
                $this->prefix
            );
            
            // Flush test database before each test
            $this->redis->flush();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server is not available: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        if ($this->redis) {
            // Clean up after tests
            $this->redis->flush();
        }
    }
    
    public function testSetStoresValueInSession()
    {
        $this->driver->start();
        
        $key = 'test_key';
        $value = 'test_value';
        
        $this->driver->set($key, $value);
        $this->assertEquals($value, $this->driver->get($key));
    }
    
    public function testGetReturnsStoredValue()
    {
        $this->driver->start();
        
        $key = 'test_key';
        $value = 'test_value';
        
        $this->driver->set($key, $value);
        $result = $this->driver->get($key);
        
        $this->assertEquals($value, $result);
    }
    
    public function testGetReturnsDefaultWhenKeyNotExists()
    {
        $this->driver->start();
        
        $key = 'nonexistent_key';
        $default = 'default_value';
        
        $result = $this->driver->get($key, $default);
        $this->assertEquals($default, $result);
    }
    
    public function testGetReturnsEntireSessionWhenKeyIsNull()
    {
        $this->driver->start();
        
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');
        
        $result = $this->driver->get();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }
    
    public function testDeleteRemovesKey()
    {
        $this->driver->start();
        
        $key = 'test_key';
        $this->driver->set($key, 'test_value');
        
        $this->driver->delete($key);
        
        $this->assertNull($this->driver->get($key));
    }
    
    public function testRegenerateCreatesNewSession()
    {
        $this->driver->start();
        
        // Store data in session
        $this->driver->set('key1', 'value1');
        
        // Get current session ID
        $oldId = $this->getSessionIdFromCookie();
        
        // Regenerate session
        $this->driver->regenerate();
        
        // Get new session ID
        $newId = $this->getSessionIdFromCookie();
        
        // IDs should be different
        $this->assertNotEquals($oldId, $newId);
        
        // Data should be preserved
        $this->assertEquals('value1', $this->driver->get('key1'));
    }
    
    public function testDestroyRemovesAllData()
    {
        $this->driver->start();
        
        // Store data in session
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');
        
        // Destroy session
        $this->driver->destroy();
        
        // Session should be empty
        $this->driver->start();
        $this->assertEmpty($this->driver->get());
    }
    
    public function testStartedReturnsTrueAfterStart()
    {
        $this->assertFalse($this->driver->started());
        
        $this->driver->start();
        
        $this->assertTrue($this->driver->started());
    }
    
    public function testCanStoreComplexData()
    {
        $this->driver->start();
        
        $data = [
            'user' => [
                'id' => 123,
                'name' => 'Test User',
                'roles' => ['admin', 'editor'],
                'active' => true,
            ],
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
        ];
        
        $this->driver->set('profile', $data);
        
        $result = $this->driver->get('profile');
        
        $this->assertEquals($data, $result);
    }
    
    /**
     * Helper method to get session ID from cookie
     */
    private function getSessionIdFromCookie()
    {
        return $_COOKIE[$this->sessionName] ?? null;
    }
}
