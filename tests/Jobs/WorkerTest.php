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

class MockJobEngine extends BaseEngine
{
    private $jobs = [];
    private $processedJobs = [];
    private $failedJobs = [];

    public function addJob(string $job, array $payload = [], string $delay = 'now', string $queue = 'default'): void
    {
        $this->jobs[] = [
            'id' => uniqid(),
            'handler' => $job,
            'payload' => $payload,
            'delay' => $delay,
            'queue' => $queue,
            'attempts' => 0,
        ];
    }

    public function fetchNextJob(?string $queue = null)
    {
        if (empty($this->jobs)) {
            return null;
        }
        
        foreach ($this->jobs as $index => $job) {
            if ($queue === null || $job['queue'] === $queue) {
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
