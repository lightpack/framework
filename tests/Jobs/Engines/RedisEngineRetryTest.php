<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Redis\Redis;
use Lightpack\Jobs\Engines\RedisEngine;

final class RedisEngineRetryTest extends TestCase
{
    private $redis;
    private $engine;
    private $prefix = 'test_jobs:';
    
    protected function setUp(): void
    {
        parent::setUp();
        
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
    
    protected function tearDown(): void
    {
        if ($this->redis) {
            // Clean up after tests
            $this->redis->flush();
        }
        
        parent::tearDown();
    }
    
    public function testCanRetrySpecificFailedJob()
    {
        // Add a job and mark it as failed
        $this->engine->addJob('TestJob', ['key' => 'value'], 'now', 'default');
        $job = $this->engine->fetchNextJob();
        $exception = new \Exception('Test exception');
        $this->engine->markFailedJob($job, $exception);
        
        // Retry the failed job
        $count = $this->engine->retryFailedJobs($job->id);
        
        $this->assertEquals(1, $count);
        
        // Verify job was reset
        $jobKey = $this->prefix . 'job:' . $job->id;
        $updatedJob = $this->redis->get($jobKey);
        
        $this->assertEquals('new', $updatedJob['status']);
        $this->assertEquals(0, $updatedJob['attempts']);
        $this->assertNull($updatedJob['exception']);
        $this->assertNull($updatedJob['failed_at']);
        
        // Verify job was removed from failed queue
        $failedKey = $this->prefix . 'failed';
        $failedJobIds = $this->redis->zRange($failedKey, 0, -1);
        
        $this->assertNotContains($job->id, $failedJobIds);
        
        // Verify job was added back to queue
        $queueKey = $this->prefix . 'queue:default';
        $jobIds = $this->redis->zRange($queueKey, 0, -1);
        
        $this->assertContains($job->id, $jobIds);
    }
    
    public function testCanRetryAllFailedJobs()
    {
        // Add multiple jobs and mark them as failed
        $jobIds = [];
        for ($i = 0; $i < 3; $i++) {
            $this->engine->addJob('TestJob', ['key' => 'value' . $i], 'now', 'default');
            $job = $this->engine->fetchNextJob();
            $exception = new \Exception('Test exception');
            $this->engine->markFailedJob($job, $exception);
            $jobIds[] = $job->id;
        }
        
        // Retry all failed jobs
        $count = $this->engine->retryFailedJobs();
        
        $this->assertEquals(3, $count);
        
        // Verify all jobs were reset
        foreach ($jobIds as $jobId) {
            $jobKey = $this->prefix . 'job:' . $jobId;
            $updatedJob = $this->redis->get($jobKey);
            
            $this->assertEquals('new', $updatedJob['status']);
            $this->assertEquals(0, $updatedJob['attempts']);
        }
        
        // Verify failed queue is empty
        $failedKey = $this->prefix . 'failed';
        $failedJobIds = $this->redis->zRange($failedKey, 0, -1);
        
        $this->assertEmpty($failedJobIds);
    }
    
    public function testReturnsZeroForNonExistentJob()
    {
        $count = $this->engine->retryFailedJobs('non_existent_job_id');
        
        $this->assertEquals(0, $count);
    }
}
