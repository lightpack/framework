<?php

namespace Lightpack\Tests\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\FileDriver;
use Lightpack\Container\Container;
use Lightpack\Utils\Lock;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    private Lock $lock;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = __DIR__ . '/tmp';
        mkdir($this->cacheDir);

        $container = Container::getInstance();

        $container->register('cache', function ($container) {
            return new Cache(new FileDriver($this->cacheDir));
        });

        $this->lock = new Lock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        array_map('unlink', glob($this->cacheDir . '/*'));
        rmdir($this->cacheDir);
    }

    public function testLockCanBeAcquired()
    {
        $this->assertTrue($this->lock->acquire('test-lock'));
        $this->assertTrue($this->lock->has('test-lock'));
    }

    public function testLockCanBeReleased()
    {
        $this->lock->acquire('test-lock');
        $this->lock->release('test-lock');
        $this->assertFalse($this->lock->has('test-lock'));
    }

    public function testLockCannotBeAcquiredTwice()
    {
        $this->assertTrue($this->lock->acquire('test-lock'));
        $this->assertFalse($this->lock->acquire('test-lock'));
        $this->assertTrue($this->lock->has('test-lock'));
    }

    public function testLockExpiresAfterTtl()
    {
        $this->lock->acquire('test-lock', 1); // 1 second TTL
        sleep(2);
        $this->assertFalse($this->lock->has('test-lock'));
    }
}
