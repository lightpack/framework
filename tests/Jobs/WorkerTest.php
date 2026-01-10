<?php

namespace Lightpack\Tests\Jobs;

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Worker;
use Lightpack\Jobs\Connection;
use Lightpack\Container\Container;
use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Tests\Jobs\Mocks\MockJob;
use Lightpack\Tests\Jobs\Mocks\FailingMockJob;
use Lightpack\Tests\Jobs\Mocks\MockJobEngine;

class WorkerTest extends TestCase
{
    private $worker;
    private $container;
    private $jobEngine;

    protected function setUp(): void
    {
        // Setup real container instance with cache service
        $this->container = Container::getInstance();
        
        // Register cache service
        if (!$this->container->has('cache')) {
            $this->container->register('cache', fn() => new Cache(new ArrayDriver()));
        }
        
        // Setup job engine
        $this->jobEngine = new MockJobEngine();
        $reflection = new \ReflectionClass(Connection::class);
        $engine = $reflection->getProperty('engine');
        $engine->setAccessible(true);
        $engine->setValue(null, $this->jobEngine);
        
        // Create worker
        $this->worker = new Worker([
            'sleep' => 1,
            'queues' => ['default', 'high'],
            'cooldown' => 5,
        ]);
    }

    public function testWorkerInitialization()
    {
        $this->assertInstanceOf(Worker::class, $this->worker);
    }

    public function testProcessQueue()
    {
        // Add a job
        $this->jobEngine->addJob(MockJob::class, ['test' => 'data'], 'now', 'default');
        
        // Process the queue
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('processQueue');
        $method->setAccessible(true);
        
        // Process default queue
        $method->invoke($this->worker, 'default');
        
        // Verify job was processed (deleted from queue)
        $processedJobs = $this->jobEngine->getProcessedJobs();
        $this->assertCount(1, $processedJobs);
        $this->assertEquals(MockJob::class, $processedJobs[0]['handler']);
    }

    public function testJobFailureAndRetry()
    {
        // Add a failing job
        $this->jobEngine->addJob(FailingMockJob::class, ['test' => 'data'], 'now', 'default');
        
        // Process the queue
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('processQueue');
        $method->setAccessible(true);
        
        // Process default queue
        $method->invoke($this->worker, 'default');
        
        // Should not be marked as failed since we're retrying
        $failedJobs = $this->jobEngine->getFailedJobs();
        $this->assertCount(0, $failedJobs);
        
        // Should be released for retry with delay
        $remainingJobs = $this->jobEngine->getQueuedJobs();
        $this->assertCount(1, $remainingJobs);
        $this->assertEquals(FailingMockJob::class, $remainingJobs[0]['handler']);
        $this->assertEquals('+5 seconds', $remainingJobs[0]['delay']);
        $this->assertEquals(1, $remainingJobs[0]['attempts']);
    }

    public function testJobExhaustsRetries()
    {
        // Add a failing job that has already been attempted twice
        $this->jobEngine->addJob(FailingMockJob::class, ['test' => 'data'], 'now', 'default', 2);
        
        // Process the queue
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('processQueue');
        $method->setAccessible(true);
        
        // Process default queue
        $method->invoke($this->worker, 'default');
        
        // Verify job was marked as failed
        $failedJobs = $this->jobEngine->getFailedJobs();
        $this->assertCount(1, $failedJobs);
        $this->assertInstanceOf(\RuntimeException::class, $failedJobs[0]['exception']);
    }

    public function testWorkerCooldown()
    {
        $reflection = new \ReflectionClass($this->worker);
        $cooldownProperty = $reflection->getProperty('cooldown');
        $cooldownProperty->setAccessible(true);
        
        $this->assertEquals(5, $cooldownProperty->getValue($this->worker));
    }

    protected function tearDown(): void
    {
        // Reset Connection engine
        $reflection = new \ReflectionClass(Connection::class);
        $engine = $reflection->getProperty('engine');
        $engine->setAccessible(true);
        $engine->setValue(null, null);
        
        // Clear cache
        if ($this->container->has('cache')) {
            $cache = $this->container->get('cache');
            if (method_exists($cache, 'flush')) {
                $cache->flush();
            }
        }
    }
}
