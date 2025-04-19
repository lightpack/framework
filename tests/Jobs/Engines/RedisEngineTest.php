<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Redis\Redis;
use Lightpack\Jobs\Engines\RedisEngine;

final class RedisEngineTest extends TestCase
{
    private $redis;
    private $engine;
    private $prefix = 'test_jobs:';
    
    public function setUp(): void
    {
        // Skip tests if Redis extension is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not available');
        }
        
        try {
            $this->redis = new Redis([
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 15, // Use database 15 for testing
            ]);
            
            // Test connection
            $this->redis->connect();
            
            // Create engine with test prefix
            $this->engine = new RedisEngine($this->redis, $this->prefix);
            
            // Flush test database before each test
            $this->redis->flush();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server is not available: ' . $e->getMessage());
        }
    }
    
    public function tearDown(): void
    {
        if ($this->redis) {
            // Clean up after tests
            $this->redis->flush();
        }
    }
    
    public function testCanAddJob()
    {
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Check if job was added to queue
        $queueKey = $this->prefix . 'queue:' . $queue;
        $jobIds = $this->redis->zRange($queueKey, 0, -1);
        
        $this->assertCount(1, $jobIds);
        
        // Check job data
        $jobKey = $this->prefix . 'job:' . $jobIds[0];
        $job = $this->redis->get($jobKey);
        
        $this->assertNotNull($job);
        $this->assertEquals($jobHandler, $job['handler']);
        $this->assertEquals(json_encode($payload), $job['payload']);
        $this->assertEquals('new', $job['status']);
        $this->assertEquals($queue, $job['queue']);
        $this->assertEquals(0, $job['attempts']);
    }
    
    public function testCanFetchNextJob()
    {
        // Add a job
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Fetch the job
        $job = $this->engine->fetchNextJob();
        
        $this->assertNotNull($job);
        $this->assertEquals($jobHandler, $job->handler);
        $this->assertEquals($payload, $job->payload);
        $this->assertEquals('queued', $job->status);
        $this->assertEquals($queue, $job->queue);
        $this->assertEquals(1, $job->attempts);
    }
    
    public function testCanDeleteJob()
    {
        // Add a job
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Fetch the job
        $job = $this->engine->fetchNextJob();
        
        // Delete the job
        $this->engine->deleteJob($job);
        
        // Check if job was deleted
        $jobKey = $this->prefix . 'job:' . $job->id;
        $exists = $this->redis->exists($jobKey);
        
        $this->assertFalse($exists);
    }
    
    public function testCanMarkJobAsFailed()
    {
        // Add a job
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Fetch the job
        $job = $this->engine->fetchNextJob();
        
        // Mark job as failed
        $exception = new \Exception('Test exception');
        $this->engine->markFailedJob($job, $exception);
        
        // Check if job was marked as failed
        $jobKey = $this->prefix . 'job:' . $job->id;
        $updatedJob = $this->redis->get($jobKey);
        
        $this->assertEquals('failed', $updatedJob['status']);
        $this->assertNotNull($updatedJob['exception']);
        $this->assertNotNull($updatedJob['failed_at']);
        
        // Check if job was added to failed queue
        $failedQueueKey = $this->prefix . 'failed';
        $failedJobIds = $this->redis->zRange($failedQueueKey, 0, -1);
        
        $this->assertContains($job->id, $failedJobIds);
    }
    
    public function testCanReleaseJob()
    {
        // Add a job
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Fetch the job
        $job = $this->engine->fetchNextJob();
        
        // Mark job as failed
        $exception = new \Exception('Test exception');
        $this->engine->markFailedJob($job, $exception);
        
        // Release the job
        $this->engine->release($job, 'now');
        
        // Check if job was released
        $jobKey = $this->prefix . 'job:' . $job->id;
        $updatedJob = $this->redis->get($jobKey);
        
        $this->assertEquals('new', $updatedJob['status']);
        $this->assertNull($updatedJob['exception']);
        $this->assertNull($updatedJob['failed_at']);
        $this->assertEquals(2, $updatedJob['attempts']);
        
        // Check if job was added back to queue
        $queueKey = $this->prefix . 'queue:' . $queue;
        $jobIds = $this->redis->zRange($queueKey, 0, -1);
        
        $this->assertContains($job->id, $jobIds);
        
        // Check if job was removed from failed queue
        $failedQueueKey = $this->prefix . 'failed';
        $failedJobIds = $this->redis->zRange($failedQueueKey, 0, -1);
        
        $this->assertNotContains($job->id, $failedJobIds);
    }
    
    public function testDelayedJobsAreNotFetchedBeforeScheduledTime()
    {
        // Add a delayed job (1 hour from now)
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = '+1 hour';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Try to fetch the job
        $job = $this->engine->fetchNextJob();
        
        // Job should not be fetched yet
        $this->assertNull($job);
    }
    
    public function testCanFetchJobsFromSpecificQueue()
    {
        // Add jobs to different queues
        $this->engine->addJob('TestJob1', ['key' => 'value1'], 'now', 'queue1');
        $this->engine->addJob('TestJob2', ['key' => 'value2'], 'now', 'queue2');
        
        // Fetch job from queue1
        $job1 = $this->engine->fetchNextJob('queue1');
        
        $this->assertNotNull($job1);
        $this->assertEquals('TestJob1', $job1->handler);
        $this->assertEquals(['key' => 'value1'], $job1->payload);
        $this->assertEquals('queue1', $job1->queue);
        
        // Fetch job from queue2
        $job2 = $this->engine->fetchNextJob('queue2');
        
        $this->assertNotNull($job2);
        $this->assertEquals('TestJob2', $job2->handler);
        $this->assertEquals(['key' => 'value2'], $job2->payload);
        $this->assertEquals('queue2', $job2->queue);
    }
}
