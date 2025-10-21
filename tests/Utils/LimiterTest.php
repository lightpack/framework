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
        @mkdir($this->cacheDir);

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
        // 1-second window
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(1, $this->limiter->getHits('test-key'));
    }

    public function testMultipleAttemptsWithinLimit()
    {
        // 1-second window
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(3, $this->limiter->getHits('test-key'));
    }

    public function testExceedingLimitFails()
    {
        // First 3 attempts should succeed (1-second window)
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        $this->assertTrue($this->limiter->attempt('test-key', 3, 1));
        
        // Fourth attempt should fail
        $this->assertFalse($this->limiter->attempt('test-key', 3, 1));
        $this->assertEquals(3, $this->limiter->getHits('test-key'));
    }

    public function testLimitResetsAfterExpiry()
    {
        // Use up all attempts (2 attempts in 1-second window)
        $this->limiter->attempt('test-key', 2, 1);
        $this->limiter->attempt('test-key', 2, 1);
        $this->assertEquals(2, $this->limiter->getHits('test-key'));

        // Make cache appear expired by modifying the TTL
        $file = $this->cacheDir . '/' . sha1('limiter:test-key');
        $contents = unserialize(file_get_contents($file));
        $contents['ttl'] = time() - 60;  // Set TTL to 1 minute ago
        file_put_contents($file, serialize($contents));

        // Should be able to attempt again
        $this->assertTrue($this->limiter->attempt('test-key', 2, 1));
        $this->assertEquals(1, $this->limiter->getHits('test-key'));
    }

    public function testDifferentKeysTrackedSeparately()
    {
        // 1-second window
        $this->assertTrue($this->limiter->attempt('key1', 2, 1));
        $this->assertTrue($this->limiter->attempt('key2', 2, 1));

        $this->assertEquals(1, $this->limiter->getHits('key1'));
        $this->assertEquals(1, $this->limiter->getHits('key2'));
    }

    public function testSubMinuteWindow()
    {
        // 2-second window
        $this->assertTrue($this->limiter->attempt('submin-key', 2, 2));
        $this->assertTrue($this->limiter->attempt('submin-key', 2, 2));
        $this->assertFalse($this->limiter->attempt('submin-key', 2, 2));
    }

    public function testGetRemainingWithNoAttempts()
    {
        // No attempts yet - should return max
        $remaining = $this->limiter->getRemaining('new-key', 5);
        $this->assertEquals(5, $remaining);
    }

    public function testGetRemainingAfterSomeAttempts()
    {
        // Make 2 attempts out of 5
        $this->limiter->attempt('test-key', 5, 60);
        $this->limiter->attempt('test-key', 5, 60);
        
        $remaining = $this->limiter->getRemaining('test-key', 5);
        $this->assertEquals(3, $remaining);
    }

    public function testGetRemainingWhenLimitReached()
    {
        // Use all 3 attempts
        $this->limiter->attempt('test-key', 3, 60);
        $this->limiter->attempt('test-key', 3, 60);
        $this->limiter->attempt('test-key', 3, 60);
        
        $remaining = $this->limiter->getRemaining('test-key', 3);
        $this->assertEquals(0, $remaining);
    }

    public function testGetRemainingNeverNegative()
    {
        // Edge case: if somehow hits exceed max
        $this->limiter->attempt('test-key', 2, 60);
        $this->limiter->attempt('test-key', 2, 60);
        
        // Should return 0, not negative
        $remaining = $this->limiter->getRemaining('test-key', 2);
        $this->assertEquals(0, $remaining);
    }

    public function testGetHitsReturnsNullForNonExistentKey()
    {
        $hits = $this->limiter->getHits('non-existent-key');
        $this->assertNull($hits);
    }

    public function testGetHitsReturnsCorrectCount()
    {
        $this->limiter->attempt('test-key', 5, 60);
        $this->limiter->attempt('test-key', 5, 60);
        $this->limiter->attempt('test-key', 5, 60);
        
        $hits = $this->limiter->getHits('test-key');
        $this->assertEquals(3, $hits);
    }

    public function testRateLimitingAcrossMultipleKeys()
    {
        // Simulate rate limiting per user
        $this->assertTrue($this->limiter->attempt('user:1', 3, 60));
        $this->assertTrue($this->limiter->attempt('user:2', 3, 60));
        $this->assertTrue($this->limiter->attempt('user:1', 3, 60));
        
        $this->assertEquals(2, $this->limiter->getHits('user:1'));
        $this->assertEquals(1, $this->limiter->getHits('user:2'));
        $this->assertEquals(1, $this->limiter->getRemaining('user:1', 3));
        $this->assertEquals(2, $this->limiter->getRemaining('user:2', 3));
    }

    public function testHighVolumeAttempts()
    {
        // Test with higher limits
        $max = 100;
        
        // Make 50 attempts
        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue($this->limiter->attempt('high-volume', $max, 60));
        }
        
        $this->assertEquals(50, $this->limiter->getHits('high-volume'));
        $this->assertEquals(50, $this->limiter->getRemaining('high-volume', $max));
    }

    public function testZeroMaxLimit()
    {
        // Edge case: max = 0 (should always fail)
        $this->assertFalse($this->limiter->attempt('zero-limit', 0, 60));
        $this->assertEquals(0, $this->limiter->getRemaining('zero-limit', 0));
    }

    public function testOneMaxLimit()
    {
        // Edge case: max = 1 (single attempt allowed)
        $this->assertTrue($this->limiter->attempt('one-limit', 1, 60));
        $this->assertFalse($this->limiter->attempt('one-limit', 1, 60));
        
        $this->assertEquals(1, $this->limiter->getHits('one-limit'));
        $this->assertEquals(0, $this->limiter->getRemaining('one-limit', 1));
    }

    public function testRemainingDecreasesWithEachAttempt()
    {
        $max = 5;
        
        // Check remaining before any attempts
        $this->assertEquals(5, $this->limiter->getRemaining('countdown', $max));
        
        // Make attempts and verify remaining decreases
        $this->limiter->attempt('countdown', $max, 60);
        $this->assertEquals(4, $this->limiter->getRemaining('countdown', $max));
        
        $this->limiter->attempt('countdown', $max, 60);
        $this->assertEquals(3, $this->limiter->getRemaining('countdown', $max));
        
        $this->limiter->attempt('countdown', $max, 60);
        $this->assertEquals(2, $this->limiter->getRemaining('countdown', $max));
        
        $this->limiter->attempt('countdown', $max, 60);
        $this->assertEquals(1, $this->limiter->getRemaining('countdown', $max));
        
        $this->limiter->attempt('countdown', $max, 60);
        $this->assertEquals(0, $this->limiter->getRemaining('countdown', $max));
    }

    public function testKeyPrefixing()
    {
        // Ensure keys are properly prefixed to avoid collisions
        $this->limiter->attempt('test', 5, 60);
        
        // The actual cache key should be prefixed
        $hits = $this->limiter->getHits('test');
        $this->assertEquals(1, $hits);
        
        // Direct cache access without prefix should return null
        $container = Container::getInstance();
        $cache = $container->get('cache');
        $this->assertNull($cache->get('test')); // No prefix
        $this->assertNotNull($cache->get('limiter:test')); // With prefix
    }
}
