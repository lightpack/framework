<?php

namespace Lightpack\Tests\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\FileDriver;
use Lightpack\Container\Container;
use Lightpack\Utils\Limiter;
use PHPUnit\Framework\TestCase;

class LimiterTest extends TestCase
{
    private Limiter $limiter;
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

        $this->limiter = new Limiter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        array_map('unlink', glob($this->cacheDir . '/*'));
        rmdir($this->cacheDir);
    }

    public function testFirstAttemptSucceeds()
    {
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(1, $this->limiter->getHits('test-key'));
    }

    public function testMultipleAttemptsWithinLimit()
    {
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(3, $this->limiter->getHits('test-key'));
    }

    public function testExceedingLimitFails()
    {
        // First 3 attempts should succeed
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        
        // Fourth attempt should fail
        $this->assertFalse($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(3, $this->limiter->getHits('test-key'));
    }

    public function testLimitResetsAfterExpiry()
    {
        // Use up all attempts
        $this->limiter->attempt('test-key', 2, 1);
        $this->limiter->attempt('test-key', 2, 1);
        $this->assertEquals(2, $this->limiter->getHits('test-key'));

        // Make cache appear expired by modifying the TTL
        $file = $this->cacheDir . '/' . sha1('test-key');
        $contents = unserialize(file_get_contents($file));
        $contents['ttl'] = time() - 60;  // Set TTL to 1 minute ago
        file_put_contents($file, serialize($contents));

        // Should be able to attempt again
        $this->assertTrue($this->limiter->attempt('test-key', 2, 1));
        $this->assertEquals(1, $this->limiter->getHits('test-key'));
    }

    public function testDifferentKeysTrackedSeparately()
    {
        $this->assertTrue($this->limiter->attempt('key1', 2, 1));
        $this->assertTrue($this->limiter->attempt('key2', 2, 1));

        $this->assertEquals(1, $this->limiter->getHits('key1'));
        $this->assertEquals(1, $this->limiter->getHits('key2'));
    }
}
