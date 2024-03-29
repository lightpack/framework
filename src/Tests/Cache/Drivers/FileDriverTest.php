<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Drivers\FileDriver;

final class FileDriverTest extends TestCase
{
    private $cacheDir;

    public function setUp(): void
    {
        $this->cacheDir = __DIR__ . '/tmp';
        mkdir($this->cacheDir);
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->cacheDir . '/*'));
        rmdir($this->cacheDir);
    }

    public function testConstructor(): void
    {
        $cache = new FileDriver($this->cacheDir);
        $this->assertTrue(file_exists($this->cacheDir));
    }

    public function testCanStoreItem()
    {
        $cache = new FileDriver($this->cacheDir);
        $cache->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($cache->has('name'));
        $this->assertTrue($cache->get('name') === 'Lightpack');
    }

    public function testCanDeleteItem()
    {
        $cache = new FileDriver($this->cacheDir);
        $cache->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($cache->has('name'));
        $cache->delete('name');
        $this->assertFalse($cache->has('name'));
    }

    public function testCanFlushItems()
    {
        $cache = new FileDriver($this->cacheDir);
        $cache->set('key1', 'value1', time() + (5 * 60));
        $cache->set('key2', 'value2', time() + (5 * 60));

        $this->assertTrue($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));

        $cache->flush();
        
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }
}