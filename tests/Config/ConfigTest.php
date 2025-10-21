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
        $config = new \Lightpack\Config\Config();
        $config->set('name', 'Lightpack');
        $this->assertEquals('Lightpack', $config->get('name'));
    }

    public function testConfigCanParseNestedKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertEquals('1.0', $config->get('cache')['version']);
    }

    public function testConfigDoesNotAllowModifyingExistingKeys()
    {
        $config = new \Lightpack\Config\Config(__DIR__ . '/../fixtures/config');

        $this->assertEquals(1, $config->get('app.version'));

        $config->set('app.version', 2);

        $this->assertEquals('1', $config->get('app.version'));
    }
}
