<?php

declare(strict_types=1);

namespace Lightpack\Tests\Jobs;

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Worker;
use Lightpack\Jobs\Job;
use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Container\Container;

/**
 * Integration test for Worker rate limiting functionality with DatabaseEngine.
 * Tests the full flow: Engine → Worker → Limiter → Rate Limited → Release
 */
final class WorkerRateLimitingDatabaseIntegrationTest extends TestCase
{
    private $db;
    private $engine;
    private $container;
    private static $executionLog = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Reset execution log
        self::$executionLog = [];

        // Bootstrap database
        if (!isset($_ENV['MYSQL_HOST'])) {
            $this->markTestSkipped('Database configuration not available');
        }

        $config = [
            'host' => $_ENV['MYSQL_HOST'],
            'port' => $_ENV['MYSQL_PORT'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD'],
            'database' => $_ENV['MYSQL_DB'],
            'options' => null,
        ];

        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        // Setup container
        $this->container = Container::getInstance();
        $this->container->register('db', function () {
            return $this->db;
        });

        // Register cache service for rate limiting
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $this->container->register('cache', function () use ($cacheDir) {
            return new \Lightpack\Cache\Cache(
                new \Lightpack\Cache\Drivers\FileDriver($cacheDir)
            );
        });

        // Create jobs table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS jobs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                handler VARCHAR(255) NOT NULL,
                payload TEXT,
                queue VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL,
                attempts INT DEFAULT 0,
                exception TEXT,
                failed_at DATETIME,
                scheduled_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Clean up
        $this->db->query("DELETE FROM jobs");

        // Configure Connection to use DatabaseEngine BEFORE creating any engines
        putenv('JOB_ENGINE=database');
        
        // Force reset Connection's static engine so it picks up the new env var
        $reflection = new \ReflectionClass(\Lightpack\Jobs\Connection::class);
        $property = $reflection->getProperty('engine');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        // Create engine
        $this->engine = new DatabaseEngine();

        // Setup cache for rate limiting (using file cache for testing)
        putenv('CACHE_DRIVER=file');

        // Clear cache
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        }
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->query("DELETE FROM jobs");
        }

        // Clear cache
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        }

        parent::tearDown();
    }

    public function testWorkerRespectsRateLimitAndReleasesJobs()
    {
        // Dispatch 5 jobs with rate limit of 2 per second
        for ($i = 1; $i <= 5; $i++) {
            $this->engine->addJob(TestRateLimitedJob::class, ['id' => $i], 'now', 'default');
        }

        // Create worker
        $worker = new Worker(['sleep' => 0, 'queues' => ['default']]);

        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);

        // Process first 3 jobs
        for ($i = 0; $i < 3; $i++) {
            $job = $this->engine->fetchNextJob();
            if ($job) {
                $method->invoke($worker, $job);
            }
        }

        // Assertions
        // Only 2 jobs should have executed (rate limit of 2/sec)
        $this->assertEquals(2, count(self::$executionLog), 'Only 2 jobs should execute due to rate limit');

        // Check that we have jobs in the database (some new, some queued)
        $totalJobs = $this->db->query("SELECT COUNT(*) as count FROM jobs")->fetch(\PDO::FETCH_OBJ);
        $this->assertGreaterThan(0, $totalJobs->count, 'Jobs should still exist in database');
    }

    public function testRateLimitedJobsAreScheduledWithDelay()
    {
        // Dispatch 3 jobs with rate limit of 1 per second
        for ($i = 1; $i <= 3; $i++) {
            $this->engine->addJob(TestStrictRateLimitedJob::class, ['id' => $i], 'now', 'default');
        }

        // Create worker
        $worker = new Worker(['sleep' => 0, 'queues' => ['default']]);

        // Process first job
        $job1 = $this->engine->fetchNextJob();
        $this->assertNotNull($job1);

        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);
        $method->invoke($worker, $job1);

        // Try to process second job immediately
        $job2 = $this->engine->fetchNextJob();
        $this->assertNotNull($job2);
        $method->invoke($worker, $job2);

        // Second job should be rate-limited and released
        $this->assertCount(1, self::$executionLog, 'Only first job should execute');

        // Check that second job was released with future scheduled_at
        $releasedJob = $this->db->query("SELECT * FROM jobs WHERE status = 'new' ORDER BY id ASC LIMIT 1")->fetch(\PDO::FETCH_OBJ);
        $this->assertNotNull($releasedJob);

        $scheduledAt = strtotime($releasedJob->scheduled_at);
        $now = time();

        // Job should be scheduled in the future or at current time (with jitter, between 1.0 and 1.2 seconds)
        $this->assertGreaterThanOrEqual($now, $scheduledAt, 'Rate-limited job should be scheduled at or after current time');
        $this->assertLessThan($now + 3, $scheduledAt, 'Delay should not be too long');
    }

    public function testRateLimitedJobsDoNotExceedMaxAttempts()
    {
        // This test verifies that jobs respect maxAttempts even when failing
        // We use a job that always throws an exception to force retry behavior
        $this->engine->addJob(TestFailingRateLimitedJob::class, ['id' => 1], 'now', 'default');

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
        $failedJob = $this->db->query("SELECT * FROM jobs WHERE status = 'failed'")->fetch(\PDO::FETCH_OBJ);
        $this->assertNotNull($failedJob, 'Job should be marked as failed after exceeding max attempts');
        $this->assertStringContainsString('Intentional failure', $failedJob->exception);
    }

    public static function logExecution($id)
    {
        self::$executionLog[] = [
            'id' => $id,
            'time' => microtime(true),
        ];
    }
}

/**
 * Test job with rate limiting (2 per second)
 */
class TestRateLimitedJob extends Job
{
    protected $attempts = 10;

    public function rateLimit(): ?array
    {
        return [
            'limit' => 2,
            'seconds' => 1,
            'jitter' => 0.2,
        ];
    }

    public function run()
    {
        WorkerRateLimitingDatabaseIntegrationTest::logExecution($this->payload['id']);
    }
}

/**
 * Test job with strict rate limiting (1 per second)
 */
class TestStrictRateLimitedJob extends Job
{
    protected $attempts = 10;

    public function rateLimit(): ?array
    {
        return [
            'limit' => 1,
            'seconds' => 1,
            'jitter' => 0.2,
        ];
    }

    public function run()
    {
        WorkerRateLimitingDatabaseIntegrationTest::logExecution($this->payload['id']);
    }
}

/**
 * Test job with low max attempts
 */
class TestLowAttemptsRateLimitedJob extends Job
{
    protected $attempts = 2;

    public function rateLimit(): ?array
    {
        return [
            'limit' => 1,
            'seconds' => 1,
            'jitter' => 0,
        ];
    }

    public function run()
    {
        WorkerRateLimitingIntegrationTest::logExecution($this->payload['id']);
    }
}

/**
 * Test job that always fails - used to test max attempts behavior
 * Since it always throws an exception, it will be released and retried
 * until max attempts is reached
 */
class TestFailingRateLimitedJob extends Job
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

    public function run()
    {
        // Always fail to force the job to be released and retried
        throw new \Exception('Intentional failure for testing max attempts');
    }
}
