<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Session\Drivers\DefaultDriver;

/**
 * @note IMPORTANT: Run these tests with --stderr option to prevent PHPUnit output from sending headers
 * Example: vendor/bin/phpunit --stderr tests/Framework/Session/Drivers/DefaultDriverTest.php
 * 
 * This is necessary because:
 * 1. PHPUnit's output normally goes to stdout, which sends headers
 * 2. Sessions must be started before any headers are sent
 * 3. Using --stderr prevents PHPUnit's output from triggering headers
 */
class SessionDefaultDriverTest extends TestCase
{
    private $driver;
    private $sessionBackup;

    protected function setUp(): void
    {
        // Clean any previous session and output
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent - cannot test session functionality');
        }

        // Start with clean session
        $_SESSION = [];
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Browser';
        
        $this->driver = new DefaultDriver('test_session');
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testSetStoresValueInSession()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->driver->set($key, $value);
        $this->assertEquals($value, $_SESSION[$key]);
    }

    public function testGetReturnsStoredValue()
    {
        $key = 'test_key';
        $value = 'test_value';
        $_SESSION[$key] = $value;

        $result = $this->driver->get($key);
        $this->assertEquals($value, $result);
    }

    public function testGetReturnsDefaultWhenKeyNotExists()
    {
        $key = 'nonexistent_key';
        $default = 'default_value';

        $result = $this->driver->get($key, $default);
        $this->assertEquals($default, $result);
    }

    public function testGetReturnsEntireSessionWhenKeyIsNull()
    {
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';

        $result = $this->driver->get();
        $this->assertEquals($_SESSION, $result);
    }

    public function testDeleteRemovesKey()
    {
        $key = 'test_key';
        $_SESSION[$key] = 'test_value';

        $this->driver->delete($key);
        $this->assertArrayNotHasKey($key, $_SESSION);
    }

    public function testRegenerateCreatesNewSessionId()
    {
        $oldId = session_id();
        $this->driver->regenerate();
        $newId = session_id();

        $this->assertNotEquals($oldId, $newId);
    }

    public function testVerifyAgentReturnsTrueForMatchingAgent()
    {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $this->assertTrue($this->driver->verifyAgent());
    }

    public function testVerifyAgentReturnsFalseForMismatchedAgent()
    {
        $_SESSION['user_agent'] = 'Different Browser';
        $this->assertFalse($this->driver->verifyAgent());
    }

    public function testDestroyRemovesAllSessionData()
    {
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';

        $this->driver->destroy();
        
        $this->assertEmpty($_SESSION);
    }

    public function testStartedReturnsTrueWhenSessionActive()
    {
        $this->assertTrue($this->driver->started());
    }
}
