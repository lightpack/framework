<?php

namespace Lightpack\Tests\Jobs;

use Lightpack\Jobs\Job;
use Lightpack\Jobs\Worker;
use Lightpack\Tests\Jobs\Mocks\MockJobEngine;
use PHPUnit\Framework\TestCase;

class PermanentJobFailureTest extends TestCase
{
    private $engine;
    private $worker;

    protected function setUp(): void
    {
        $this->engine = new MockJobEngine();
        
        // Register cache service for rate limiting (Worker needs it)
        $container = \Lightpack\Container\Container::getInstance();
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache_permanent_test';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $container->register('cache', function () use ($cacheDir) {
            return new \Lightpack\Cache\Cache(
                new \Lightpack\Cache\Drivers\FileDriver($cacheDir)
            );
        });
        
        // Set environment to use sync engine (will be overridden by direct injection)
        putenv('JOB_ENGINE=sync');
        
        // Force reset Connection's static engine
        $reflection = new \ReflectionClass(\Lightpack\Jobs\Connection::class);
        $property = $reflection->getProperty('engine');
        $property->setAccessible(true);
        $property->setValue(null, $this->engine);
        
        $this->worker = new Worker(['sleep' => 0, 'queues' => ['default']]);
    }

    protected function tearDown(): void
    {
        // Clean up cache files (but keep directory for other tests)
        $cacheDir = sys_get_temp_dir() . '/lightpack_cache_permanent_test';
        if (is_dir($cacheDir)) {
            $files = glob("$cacheDir/*");
            if ($files) {
                array_map('unlink', $files);
            }
        }
        
        // Reset Connection's static engine
        $reflection = new \ReflectionClass(\Lightpack\Jobs\Connection::class);
        $property = $reflection->getProperty('engine');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        parent::tearDown();
    }

    public function testPermanentFailureDoesNotRetry()
    {
        // Add a job that will throw PermanentJobFailureException
        $this->engine->addJob(PermanentFailureJob::class, ['test' => 'data'], 'now', 'default', 0);
        
        // Process the job
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);
        
        $job = $this->engine->fetchNextJob();
        $method->invoke($this->worker, $job);
        
        // Job should be marked as failed immediately
        $failedJobs = $this->engine->getFailedJobs();
        $this->assertCount(1, $failedJobs);
        
        // Job should NOT be released back to queue
        $queuedJobs = $this->engine->getQueuedJobs();
        $this->assertCount(0, $queuedJobs);
        
        // Exception message should be preserved
        $this->assertStringContainsString('Permanent failure', $failedJobs[0]['exception']->getMessage());
    }

    public function testPermanentFailureWithMultipleAttemptsConfigured()
    {
        // Even with maxAttempts = 3, permanent failure should fail immediately
        $this->engine->addJob(PermanentFailureJobWithRetries::class, ['test' => 'data'], 'now', 'default', 0);
        
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);
        
        $job = $this->engine->fetchNextJob();
        $this->assertEquals(1, $job->attempts); // First attempt
        
        $method->invoke($this->worker, $job);
        
        // Should fail immediately without using other attempts
        $failedJobs = $this->engine->getFailedJobs();
        $this->assertCount(1, $failedJobs);
        
        // Should NOT be released for retry
        $queuedJobs = $this->engine->getQueuedJobs();
        $this->assertCount(0, $queuedJobs);
    }

    public function testTemporaryFailureStillRetries()
    {
        // Regular exceptions should still retry
        $this->engine->addJob(TemporaryFailureJob::class, ['test' => 'data'], 'now', 'default', 0);
        
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);
        
        $job = $this->engine->fetchNextJob();
        $method->invoke($this->worker, $job);
        
        // Job should be released back to queue (not failed yet)
        $queuedJobs = $this->engine->getQueuedJobs();
        $this->assertCount(1, $queuedJobs);
        
        // Should NOT be in failed jobs yet
        $failedJobs = $this->engine->getFailedJobs();
        $this->assertCount(0, $failedJobs);
    }

    public function testOnFailureCalledForPermanentFailure()
    {
        PermanentFailureJobWithHooks::$onFailureCalled = false;
        
        $this->engine->addJob(PermanentFailureJobWithHooks::class, ['test' => 'data'], 'now', 'default', 0);
        
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('dispatchJob');
        $method->setAccessible(true);
        
        $job = $this->engine->fetchNextJob();
        $method->invoke($this->worker, $job);
        
        // onFailure hook should be called
        $this->assertTrue(PermanentFailureJobWithHooks::$onFailureCalled);
    }
}

/**
 * Test job that uses failPermanently() helper
 */
class PermanentFailureJob extends Job
{
    public function run()
    {
        $this->failPermanently('Permanent failure: insufficient balance');
    }
}

/**
 * Test job with multiple attempts configured but uses failPermanently()
 */
class PermanentFailureJobWithRetries extends Job
{
    protected $attempts = 3;
    
    public function run()
    {
        $this->failPermanently('Permanent failure: invalid data');
    }
}

/**
 * Test job that throws regular exception (should retry)
 */
class TemporaryFailureJob extends Job
{
    protected $attempts = 3;
    
    public function run()
    {
        throw new \RuntimeException('Temporary failure: network timeout');
    }
}

/**
 * Test job with onFailure hook
 */
class PermanentFailureJobWithHooks extends Job
{
    public static $onFailureCalled = false;
    
    public function run()
    {
        $this->failPermanently('Permanent failure');
    }
    
    public function onFailure()
    {
        self::$onFailureCalled = true;
    }
}
