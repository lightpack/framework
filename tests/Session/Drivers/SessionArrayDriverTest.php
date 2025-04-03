<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Session\Drivers\ArrayDriver;

class SessionArrayDriverTest extends TestCase
{
    private $driver;

    protected function setUp(): void
    {
        $this->driver = new ArrayDriver();
    }

    public function testSetStoresValue()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->driver->set($key, $value);
        $this->assertEquals($value, $this->driver->get($key));
    }

    public function testGetReturnsStoredValue()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->driver->set($key, $value);
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

    public function testGetReturnsEntireStoreWhenKeyIsNull()
    {
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');

        $result = $this->driver->get();
        
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }

    public function testDeleteRemovesKey()
    {
        $key = 'test_key';
        $this->driver->set($key, 'test_value');

        $this->driver->delete($key);
        $this->assertNull($this->driver->get($key));
    }

    public function testRegenerateAlwaysReturnsTrue()
    {
        $this->assertTrue($this->driver->regenerate());
    }

    public function testDestroyRemovesAllData()
    {
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');

        $this->driver->destroy();
        
        $this->assertEmpty($this->driver->get());
    }

    public function testStartedAlwaysReturnsTrue()
    {
        $this->assertTrue($this->driver->started());
    }
}
