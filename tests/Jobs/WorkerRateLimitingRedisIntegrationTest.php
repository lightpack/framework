<?php

namespace Lightpack\Tests\Jobs;

use Lightpack\Jobs\Job;
use Lightpack\Jobs\Worker;
use Lightpack\Jobs\Connection;
use Lightpack\Jobs\Engines\RedisEngine;
use Lightpack\Redis\Redis;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Worker rate limiting with Redis engine.
 * 
 * These tests verify that the Worker correctly integrates with the RedisEngine
 * and Limiter to enforce rate limits, release rate-limited jobs back to the queue,
 * and handle jobs that exceed their maximum attempts.
 */
class WorkerRateLimitingRedisIntegrationTest extends TestCase
{
    protected RedisEngine $engine;
    protected Redis $redis;
    protected static $executionLog = [];

    protected function setUp(): void
    {
        // Reset execution log
        self::$executionLog = [];
        
        // Clear cache FIRST to ensure clean state
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        } else {
            mkdir($cacheDir, 0777, true);
        }
        
        // Create Redis connection
        $this->redis = new Redis();
        
        // Try to connect to Redis, skip test if not available
        try {
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->ping();
            // Use database 1 for tests to avoid conflicts
            $this->redis->select(1);
            // Flush this test database completely for clean state
            $this->redis->flushDB();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis is not available: ' . $e->getMessage());
        }
        
        // Setup container - force fresh registrations
        $container = \Lightpack\Container\Container::getInstance();
        
        // Unregister existing services to ensure clean state
        $reflection = new \ReflectionClass($container);
        $servicesProperty = $reflection->getProperty('services');
        $servicesProperty->setAccessible(true);
        $services = $servicesProperty->getValue($container);
        unset($services['redis'], $services['cache']);
        $servicesProperty->setValue($container, $services);
        
        // Register Redis instance in container so Connection can use it
        $container->register('redis', function() {
            return $this->redis;
        });
        
        // Register cache service for rate limiting
        $container->register('cache', function () use ($cacheDir) {
            return new \Lightpack\Cache\Cache(
                new \Lightpack\Cache\Drivers\FileDriver($cacheDir)
            );
        });
        
        // Configure Connection to use RedisEngine BEFORE creating any engines
        putenv('JOB_ENGINE=redis');
        putenv('REDIS_JOB_PREFIX=test_jobs:');
        
        // Force reset Connection's static engine so it picks up the new env var
        $reflection = new \ReflectionClass(\Lightpack\Jobs\Connection::class);
        $property = $reflection->getProperty('engine');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        // Create engine
        $this->engine = new RedisEngine($this->redis, 'test_jobs:');

        // Setup cache for rate limiting (using file cache for testing)
        putenv('CACHE_DRIVER=file');
    }

    protected function tearDown(): void
    {
        // Clean up Redis test database
        if ($this->redis) {
            $this->redis->flushDB();
            $this->redis->select(0); // Switch back to default DB
        }
        
        // Clear cache
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        }
        
        // Clear execution log
        self::$executionLog = [];
    }

    public function testWorkerRespectsRateLimitAndReleasesJobs()
    {
        // Add 5 jobs to the queue
        for ($i = 1; $i <= 5; $i++) {
            $this->engine->addJob(TestRedisRateLimitedJob::class, ['id' => $i], 'now', 'default');
        }

        // Create worker and dispatch jobs
        $worker = new Worker(['sleep' => 0, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);

        // Process first 3 jobs (same as DatabaseEngine test)
        for ($i = 0; $i < 3; $i++) {
            $job = $this->engine->fetchNextJob();
            if ($job) {
                $method->invoke($worker, $job);
            }
        }

        // Verify only 2 jobs executed (rate limit: 2 per second)
        $this->assertEquals(2, count(self::$executionLog));
        $this->assertEquals([1, 2], self::$executionLog);

        // Verify the 3rd job was released back to the queue (not deleted)
        // Check Redis for jobs with status 'new'
        $keys = $this->redis->keys('test_jobs:job:*');
        $releasedJobs = 0;
        foreach ($keys as $key) {
            $job = $this->redis->get($key);
            if ($job && $job['status'] === 'new') {
                $releasedJobs++;
            }
        }
        $this->assertGreaterThanOrEqual(1, $releasedJobs, 'At least one job should be released back to queue');
    }

    public function testRateLimitedJobsAreScheduledWithDelay()
    {
        // Add 2 jobs - first should execute, second should be rate limited
        $this->engine->addJob(TestRedisStrictRateLimitedJob::class, ['id' => 1], 'now', 'default');
        $this->engine->addJob(TestRedisStrictRateLimitedJob::class, ['id' => 2], 'now', 'default');

        $worker = new Worker(['sleep' => 0, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);

        // Process first job - should execute
        $job1 = $this->engine->fetchNextJob();
        $this->assertNotNull($job1);
        $method->invoke($worker, $job1);

        // Process second job - should be rate limited and released
        $job2 = $this->engine->fetchNextJob();
        $this->assertNotNull($job2);
        $method->invoke($worker, $job2);

        // Verify only first job executed
        $this->assertEquals(1, count(self::$executionLog));

        // Verify second job was released with a future scheduled_at
        $jobData = $this->redis->get('test_jobs:job:' . $job2->id);
        $this->assertNotNull($jobData);
        $this->assertEquals('new', $jobData['status']);
        
        // Check that scheduled_at is in the future (with jitter, should be between 5-10 seconds)
        $scheduledAt = strtotime($jobData['scheduled_at']);
        $now = time();
        $delay = $scheduledAt - $now;
        
        $this->assertGreaterThanOrEqual(4, $delay, 'Job should be delayed by at least 4 seconds');
        $this->assertLessThanOrEqual(30, $delay, 'Job should be delayed by at most 30 seconds (5s base + jitter can add up to 100% + tolerance)');
    }

    public function testRateLimitedJobsDoNotExceedMaxAttempts()
    {
        // This test verifies that jobs respect maxAttempts even when failing
        // We use a job that always throws an exception to force retry behavior
        $this->engine->addJob(TestRedisFailingRateLimitedJob::class, ['id' => 1], 'now', 'default');

        $worker = new Worker(['sleep' => 0, 'queues' => ['default']]);
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);

        // Attempt 1: Job fails, gets released (attempts will be 1)
        $job1 = $this->engine->fetchNextJob();
        $this->assertNotNull($job1);
        $method->invoke($worker, $job1);

        // Attempt 2: Job fails again, gets released (attempts will be 2)
        $job2 = $this->engine->fetchNextJob();
        $this->assertNotNull($job2);
        $method->invoke($worker, $job2);

        // Attempt 3: Job fails, but now attempts >= maxAttempts, should be marked as failed
        $job3 = $this->engine->fetchNextJob();
        if ($job3) {
            $method->invoke($worker, $job3);
        }

        // Verify job was marked as failed
        $jobData = $this->redis->get('test_jobs:job:' . $job1->id);
        $this->assertNotNull($jobData, 'Job should still exist in Redis');
        $this->assertEquals('failed', $jobData['status'], 'Job should be marked as failed after exceeding max attempts');
        $this->assertStringContainsString('Intentional failure', $jobData['exception']);
    }

    public static function logExecution($id)
    {
        self::$executionLog[] = $id;
    }
}

/**
 * Test job with rate limiting (2 per second)
 */
class TestRedisRateLimitedJob extends Job
{
    public function rateLimit(): ?array
    {
        return [
            'limit' => 2,
            'seconds' => 1,
            'jitter' => 0,
        ];
    }

    public function run()
    {
        WorkerRateLimitingRedisIntegrationTest::logExecution($this->payload['id']);
    }
}

/**
 * Test job with strict rate limiting (1 per 5 seconds with jitter)
 */
class TestRedisStrictRateLimitedJob extends Job
{
    protected $attempts = 5; // High enough to not fail during rate limit test
    
    public function rateLimit(): ?array
    {
        return [
            'limit' => 1,
            'seconds' => 5,
            'jitter' => 5,
        ];
    }

    public function run()
    {
        WorkerRateLimitingRedisIntegrationTest::logExecution($this->payload['id']);
    }
}

/**
 * Test job that always fails - used to test max attempts behavior
 * Since it always throws an exception, it will be released and retried
 * until max attempts is reached
 */
class TestRedisFailingRateLimitedJob extends Job
{
    protected $attempts = 2;

    public function rateLimit(): ?array
    {
        return [
            'limit' => 10,  // High limit so rate limiting doesn't interfere
            'seconds' => 1,
            'jitter' => 0,
        ];
    }

    public function retryAfter(): string
    {
        // Retry immediately so the job is available for next fetchNextJob()
        return 'now';
    }

    public function run()
    {
        // Always fail to force the job to be released and retried
        throw new \Exception('Intentional failure for testing max attempts');
    }
}
