<?php

declare(strict_types=1);

use Lightpack\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Drivers\FileDriver;

final class CacheTest extends TestCase
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
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);
        $this->assertTrue(file_exists($this->cacheDir));
    }

    public function testCanStoreItem()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);
        $cache->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($cache->has('name'));
        $this->assertTrue($cache->get('name') === 'Lightpack');
    }

    public function testCanDeleteItem()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);
        $cache->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($cache->has('name'));
        $cache->delete('name');
        $this->assertFalse($cache->has('name'));
    }

    public function testCanStoreForever()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);
        $cache->forever('name', 'Lightpack');

        $this->assertTrue($cache->has('name'));
        $this->assertTrue($cache->get('name') === 'Lightpack');
    }

    public function testCanFlushItems()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);
        $cache->set('key1', 'value1', time() + (5 * 60));
        $cache->set('key2', 'value2', time() + (5 * 60));

        $this->assertTrue($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));

        $cache->flush();
        
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function testCacheRememberItem()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);

        $value = $cache->remember('key', 5, function () {
            return 'value';
        });

        $this->assertTrue($cache->has('key'));
        $this->assertTrue($value === 'value');
    }

    public function testCacheRememberForeverItem()
    {
        $fileStorage = new FileDriver($this->cacheDir);
        $cache = new Cache($fileStorage);

        $value = $cache->rememberForever('key', function () {
            return 'value';
        });

        $this->assertTrue($cache->has('key'));
        $this->assertTrue($value === 'value');
    }
}