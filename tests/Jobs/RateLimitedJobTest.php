<?php

namespace Lightpack\Tests\Jobs;

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Job;
use Lightpack\Jobs\Worker;
use Lightpack\Jobs\Connection;
use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Container\Container;
use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Utils\Limiter;

class RateLimitedEmailJob extends Job
{
    public function rateLimit(): ?array
    {
        return ['limit' => 2, 'seconds' => 1];
    }
    
    public function run()
    {
        // Simulate email sending
    }
}

class UnlimitedJob extends Job
{
    // No rate limit
    
    public function run()
    {
        // Do work
    }
}

class CustomKeyJob extends Job
{
    public function rateLimit(): ?array
    {
        $userId = $this->payload['user_id'] ?? 'default';
        return [
            'limit' => 5,
            'seconds' => 60,
            'key' => 'custom:key:' . $userId
        ];
    }
    
    public function run()
    {
        // Do work
    }
}

class RateLimitedJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register cache service for testing
        $container = Container::getInstance();
        if (!$container->has('cache')) {
            $container->register('cache', fn() => new Cache(new ArrayDriver()));
        }
        
        // Clear cache before each test
        $cache = $container->get('cache');
        if (method_exists($cache, 'flush')) {
            $cache->flush();
        }
    }

    public function testJobHasRateLimitConfiguration()
    {
        $job = new RateLimitedEmailJob();
        $config = $job->rateLimit();
        
        $this->assertIsArray($config);
        $this->assertEquals(2, $config['limit']);
        $this->assertEquals(1, $config['seconds']);
    }

    public function testJobWithoutRateLimitReturnsNull()
    {
        $job = new UnlimitedJob();
        
        $this->assertNull($job->rateLimit());
    }

    public function testCustomRateLimitKey()
    {
        $job = new CustomKeyJob();
        $job->setPayload(['user_id' => 123]);
        $config = $job->rateLimit();
        
        $this->assertEquals('custom:key:123', $config['key']);
    }

    public function testCustomRateLimitKeyWithoutUserId()
    {
        $job = new CustomKeyJob();
        $job->setPayload([]);
        $config = $job->rateLimit();
        
        $this->assertEquals('custom:key:default', $config['key']);
    }

    public function testLimiterAllowsJobsWithinLimit()
    {
        $limiter = new Limiter();
        $key = 'test:job';
        
        // First attempt should succeed
        $this->assertTrue($limiter->attempt($key, 2, 1));
        
        // Second attempt should succeed
        $this->assertTrue($limiter->attempt($key, 2, 1));
        
        // Third attempt should fail (limit reached)
        $this->assertFalse($limiter->attempt($key, 2, 1));
    }

    public function testLimiterBlocksAfterLimitReached()
    {
        $limiter = new Limiter();
        $key = 'test:job:reset';
        
        // Use up the limit
        $this->assertTrue($limiter->attempt($key, 2, 60));
        $this->assertTrue($limiter->attempt($key, 2, 60));
        
        // Third attempt should be blocked
        $this->assertFalse($limiter->attempt($key, 2, 60));
        
        // Fourth attempt should also be blocked
        $this->assertFalse($limiter->attempt($key, 2, 60));
    }

    public function testGetRemainingAttempts()
    {
        $limiter = new Limiter();
        $key = 'test:remaining';
        
        // No attempts yet
        $this->assertEquals(3, $limiter->getRemaining($key, 3));
        
        // After one attempt
        $limiter->attempt($key, 3, 60);
        $this->assertEquals(2, $limiter->getRemaining($key, 3));
        
        // After two attempts
        $limiter->attempt($key, 3, 60);
        $this->assertEquals(1, $limiter->getRemaining($key, 3));
        
        // After three attempts
        $limiter->attempt($key, 3, 60);
        $this->assertEquals(0, $limiter->getRemaining($key, 3));
    }

    public function testRateLimitConfigurationDiffers()
    {
        $job1 = new RateLimitedEmailJob();
        $job2 = new UnlimitedJob();
        
        // Rate limited job has config
        $this->assertIsArray($job1->rateLimit());
        
        // Unlimited job has no config
        $this->assertNull($job2->rateLimit());
    }

    public function testRateLimitArrayStructure()
    {
        $job = new RateLimitedEmailJob();
        $config = $job->rateLimit();
        
        // Must have required keys
        $this->assertArrayHasKey('limit', $config);
        $this->assertArrayHasKey('seconds', $config);
        
        // Values must be integers
        $this->assertIsInt($config['limit']);
        $this->assertIsInt($config['seconds']);
    }

    public function testDefaultKeyGenerationIsCacheSafe()
    {
        // Test that default key generation works with Worker
        $job = new RateLimitedEmailJob();
        $limiter = new Limiter();
        
        // Simulate what Worker does: generate default key
        $config = $job->rateLimit();
        $key = $config['key'] ?? 'job:' . str_replace('\\', '.', get_class($job));
        
        // Key should not contain backslashes
        $this->assertStringNotContainsString('\\', $key);
        
        // Key should contain dots instead
        $this->assertStringContainsString('.', $key);
        
        // Key should start with job: prefix
        $this->assertStringStartsWith('job:', $key);
        
        // Key should work with limiter (no exceptions)
        $this->assertTrue($limiter->attempt($key, 2, 1));
        $this->assertTrue($limiter->attempt($key, 2, 1));
        $this->assertFalse($limiter->attempt($key, 2, 1)); // Third should fail
    }

    public function testRateLimitSupportsMinutes()
    {
        $worker = new Worker(['sleep' => 1, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('resolveRateLimitWindow');
        $method->setAccessible(true);
        
        $config = ['minutes' => 5];
        $seconds = $method->invoke($worker, $config);
        
        $this->assertEquals(300, $seconds); // 5 * 60
    }

    public function testRateLimitSupportsHours()
    {
        $worker = new Worker(['sleep' => 1, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('resolveRateLimitWindow');
        $method->setAccessible(true);
        
        $config = ['hours' => 1];
        $seconds = $method->invoke($worker, $config);
        
        $this->assertEquals(3600, $seconds); // 1 * 3600
    }

    public function testRateLimitSupportsDays()
    {
        $worker = new Worker(['sleep' => 1, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('resolveRateLimitWindow');
        $method->setAccessible(true);
        
        $config = ['days' => 1];
        $seconds = $method->invoke($worker, $config);
        
        $this->assertEquals(86400, $seconds); // 1 * 86400
    }

    public function testRateLimitSecondsTakesPriority()
    {
        $worker = new Worker(['sleep' => 1, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('resolveRateLimitWindow');
        $method->setAccessible(true);
        
        // When multiple units provided, seconds takes priority
        $config = ['seconds' => 90, 'minutes' => 5, 'hours' => 1];
        $seconds = $method->invoke($worker, $config);
        
        $this->assertEquals(90, $seconds);
    }

    public function testRateLimitThrowsExceptionWhenNoTimeUnit()
    {
        $worker = new Worker(['sleep' => 1, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('resolveRateLimitWindow');
        $method->setAccessible(true);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate limit configuration must specify a time unit');
        
        $config = []; // No time unit specified
        $method->invoke($worker, $config);
    }
}
