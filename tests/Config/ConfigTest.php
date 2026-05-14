<?php

use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testConfigCanAccessKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');
        $this->assertEquals('Lightpack', $config->get('app.name'));
        $this->assertEquals('lightpack', $config->get('db.name'));
    }

    public function testConfigCanAddNewKeys()
    {
        $config = new \Lightpack\Config\Config;
        $config->set('name', 'Lightpack');
        $this->assertEquals('Lightpack', $config->get('name'));
    }

    public function testConfigCanParseNestedKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertEquals('1.0', $config->get('cache')['version']);
    }

    public function testConfigAllowsModifyingExistingKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertEquals(1, $config->get('app.version'));

        $config->set('app.version', 2);

        $this->assertEquals(2, $config->get('app.version'));
    }

    public function testConfigCanCheckExistingKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertTrue($config->has('app.name'));
        $this->assertTrue($config->has('db.host'));
        $this->assertFalse($config->has('app.nonexistent'));
        $this->assertFalse($config->has('unknown.key'));
    }

    public function testConfigCanCheckNestedKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertTrue($config->has('cache.version'));
        $this->assertFalse($config->has('cache.nonexistent'));
    }
}
