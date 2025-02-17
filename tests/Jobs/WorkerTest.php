<?php

namespace Lightpack\Tests\Jobs;

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Worker;
use Lightpack\Jobs\BaseEngine;
use Lightpack\Jobs\Job;
use Lightpack\Jobs\Connection;
use Lightpack\Container\Container;

class MockJob extends Job
{
    public function run()
    {
        // Test job implementation
    }

    public function onSuccess()
    {
        // Success callback
    }
}

class FailingMockJob extends Job
{
    private $shouldFail;

    public function __construct(bool $shouldFail = true)
    {
        $this->shouldFail = $shouldFail;
    }

    public function run()
    {
        if ($this->shouldFail) {
            throw new \RuntimeException("Job failed on attempt {$this->attempts}");
        }
    }

    public function onFailure(\Throwable $e)
    {
        // Failure callback
    }

    public function maxAttempts(): int
    {
        return 3;
    }

    public function retryAfter(): string
    {
        return '+5 seconds';
    }
}

class MockJobEngine extends BaseEngine
{
    private $jobs = [];
    private $processedJobs = [];
    private $failedJobs = [];

    public function addJob(string $job, array $payload = [], string $delay = 'now', string $queue = 'default', int $attempts = 0): void
    {
        $this->jobs[] = [
            'id' => uniqid(),
            'handler' => $job,
            'payload' => $payload,
            'delay' => $delay,
            'queue' => $queue,
            'attempts' => $attempts,
        ];
    }

    public function fetchNextJob(?string $queue = null)
    {
        if (empty($this->jobs)) {
            return null;
        }
        
        foreach ($this->jobs as $index => $job) {
            if ($queue === null || $job['queue'] === $queue) {
                // Skip jobs that have a delay set (other than 'now')
                if ($job['delay'] !== 'now') {
                    continue;
                }
                
                unset($this->jobs[$index]);
                $this->jobs = array_values($this->jobs);
                $this->processedJobs[] = $job;
                return (object) $job;
            }
        }
        
        return null;
    }

    public function deleteJob($job): void
    {
        // Implementation for test purposes
    }

    public function markFailedJob($job, \Throwable $e): void
    {
        $this->failedJobs[] = [
            'job' => $job,
            'exception' => $e,
        ];
    }

    public function release($job, string $delay = 'now'): void
    {
        $job = (array) $job;
        $job['delay'] = $delay;
        $job['attempts']++;
        $this->jobs[] = $job;
    }

    public function getProcessedJobs(): array
    {
        return $this->processedJobs;
    }

    public function getQueuedJobs(): array
    {
        return $this->jobs;
    }

    public function getFailedJobs(): array
    {
        return $this->failedJobs;
    }
}

class WorkerTest extends TestCase
{
    private Worker $worker;
    private MockJobEngine $jobEngine;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup mock job engine and inject it into Connection
        $this->jobEngine = new MockJobEngine();
        $reflection = new \ReflectionClass(Connection::class);
        $engine = $reflection->getProperty('engine');
        $engine->setAccessible(true);
        $engine->setValue(null, $this->jobEngine);
        
        // Setup container
        Container::destroy();
        $this->container = $this->createMock(Container::class);
        $reflection = new \ReflectionClass(Container::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $this->container);
        
        // Create worker with test configuration
        $this->worker = new Worker([
            'sleep' => 1,
            'queues' => ['default', 'high'],
            'cooldown' => 5,
        ]);
    }

    public function testWorkerInitialization()
    {
        $reflection = new \ReflectionClass($this->worker);
        
        $sleepInterval = $reflection->getProperty('sleepInterval');
        $sleepInterval->setAccessible(true);
        
        $queues = $reflection->getProperty('queues');
        $queues->setAccessible(true);
        
        $cooldown = $reflection->getProperty('cooldown');
        $cooldown->setAccessible(true);
        
        $this->assertEquals(1, $sleepInterval->getValue($this->worker));
        $this->assertEquals(['default', 'high'], $queues->getValue($this->worker));
        $this->assertEquals(5, $cooldown->getValue($this->worker));
    }

    public function testProcessQueue()
    {
        // Add some test jobs
        $this->jobEngine->addJob(MockJob::class, ['test' => 'data'], 'now', 'default');
        $this->jobEngine->addJob(MockJob::class, ['test' => 'data2'], 'now', 'high');
        
        // Create mock container resolution
        $mockJob = new MockJob();
        $this->container
            ->expects($this->once())
            ->method('resolve')
            ->with(MockJob::class)
            ->willReturn($mockJob);
            
        $this->container
            ->expects($this->once())
            ->method('call')
            ->with(MockJob::class, 'run')
            ->willReturn(null);
            
        $this->container
            ->expects($this->once())
            ->method('callIf')
            ->with(MockJob::class, 'onSuccess')
            ->willReturn(null);
        
        // Process the queue
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('processQueue');
        $method->setAccessible(true);
        
        // Process default queue
        $method->invoke($this->worker, 'default');
        
        // Verify job was processed
        $processedJobs = $this->jobEngine->getProcessedJobs();
        $this->assertCount(1, $processedJobs);
        $this->assertEquals(MockJob::class, $processedJobs[0]['handler']);
        $this->assertEquals(['test' => 'data'], $processedJobs[0]['payload']);
        
        // Verify remaining job in high queue
        $remainingJobs = $this->jobEngine->getQueuedJobs();
        $this->assertCount(1, $remainingJobs);
        $this->assertEquals('high', $remainingJobs[0]['queue']);
    }

    public function testJobFailureAndRetry()
    {
        // Add a failing job
        $this->jobEngine->addJob(FailingMockJob::class, ['test' => 'data'], 'now', 'default');
        
        // Create mock container resolution with a real job instance
        $mockJob = new FailingMockJob();
        $this->container
            ->expects($this->once())
            ->method('resolve')
            ->with(FailingMockJob::class)
            ->willReturn($mockJob);
            
        $this->container
            ->expects($this->once())
            ->method('call')
            ->with(FailingMockJob::class, 'run')
            ->will($this->throwException(new \RuntimeException('Job failed on attempt 1')));
            
        $this->container
            ->expects($this->never())  // Should not call onFailure since we're retrying
            ->method('callIf')
            ->with(FailingMockJob::class, 'onFailure');
        
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
        // Add a failing job that's already been attempted twice (will fail on 3rd and final attempt)
        $this->jobEngine->addJob(FailingMockJob::class, ['test' => 'data'], 'now', 'default', 2);
        
        // Create mock container resolution
        $mockJob = new FailingMockJob();
        $this->container
            ->expects($this->once())
            ->method('resolve')
            ->with(FailingMockJob::class)
            ->willReturn($mockJob);
            
        $this->container
            ->expects($this->once())
            ->method('call')
            ->with(FailingMockJob::class, 'run')
            ->will($this->throwException(new \RuntimeException('Job failed on final attempt')));
            
        $this->container
            ->expects($this->once())
            ->method('callIf')
            ->with(FailingMockJob::class, 'onFailure')
            ->willReturn(null);
        
        // Process the queue
        $reflection = new \ReflectionClass($this->worker);
        $method = $reflection->getMethod('processQueue');
        $method->setAccessible(true);
        
        // Process default queue
        $method->invoke($this->worker, 'default');
        
        // Verify job was processed and failed
        $failedJobs = $this->jobEngine->getFailedJobs();
        $this->assertCount(1, $failedJobs);
        $this->assertInstanceOf(\RuntimeException::class, $failedJobs[0]['exception']);
        $this->assertEquals('Job failed on final attempt', $failedJobs[0]['exception']->getMessage());
        
        // Verify job was NOT released for retry (max attempts reached)
        $remainingJobs = $this->jobEngine->getQueuedJobs();
        $this->assertCount(0, $remainingJobs);
    }

    public function testWorkerCooldown()
    {
        $reflection = new \ReflectionClass($this->worker);
        
        // Set start time to simulate running for longer than cooldown
        $startTime = $reflection->getProperty('startTime');
        $startTime->setAccessible(true);
        $startTime->setValue($this->worker, time() - 6);
        
        $method = $reflection->getMethod('shouldCooldown');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->worker));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Container::destroy();
    }
}
