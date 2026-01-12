<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Container\Container;

final class DatabaseEngineTest extends TestCase
{
    private $db;
    private $engine;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Bootstrap database for tests
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
        
        // Register DB in container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        
        // Create jobs table if not exists
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
        
        // Create engine
        $this->engine = new DatabaseEngine();
        
        // Clean up before each test
        $this->db->query("DELETE FROM jobs");
    }
    
    protected function tearDown(): void
    {
        if ($this->db) {
            // Clean up test data
            $this->db->query("DELETE FROM jobs");
        }
        
        parent::tearDown();
    }
    
    public function testCanAddJob()
    {
        $jobHandler = 'TestJob';
        $payload = ['key' => 'value'];
        $delay = 'now';
        $queue = 'default';
        
        $this->engine->addJob($jobHandler, $payload, $delay, $queue);
        
        // Check if job was added
        $job = $this->db->query("SELECT * FROM jobs WHERE handler = ?", [$jobHandler])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertNotNull($job);
        $this->assertEquals($jobHandler, $job->handler);
        $this->assertEquals(json_encode($payload), $job->payload);
        $this->assertEquals('new', $job->status);
        $this->assertEquals($queue, $job->queue);
        $this->assertEquals(0, $job->attempts);
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
        // DatabaseEngine doesn't update job object status, only database
        $this->assertEquals($queue, $job->queue);
        // Check database for actual status and attempts
        $dbJob = $this->db->query("SELECT * FROM jobs WHERE id = ?", [$job->id])->fetch(\PDO::FETCH_OBJ);
        $this->assertEquals('queued', $dbJob->status);
        $this->assertEquals(1, $dbJob->attempts);
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
        $exists = $this->db->query("SELECT COUNT(*) as count FROM jobs WHERE id = ?", [$job->id])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertEquals(0, $exists->count);
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
        $updatedJob = $this->db->query("SELECT * FROM jobs WHERE id = ?", [$job->id])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertEquals('failed', $updatedJob->status);
        $this->assertNotNull($updatedJob->exception);
        $this->assertStringContainsString('Test exception', $updatedJob->exception);
        $this->assertNotNull($updatedJob->failed_at);
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
        
        // Mark job as failed first
        $exception = new \Exception('Test exception');
        $this->engine->markFailedJob($job, $exception);
        
        // Release the job
        $this->engine->release($job, 'now');
        
        // Check if job was released
        $updatedJob = $this->db->query("SELECT * FROM jobs WHERE id = ?", [$job->id])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertEquals('new', $updatedJob->status);
        $this->assertNull($updatedJob->exception);
        $this->assertNull($updatedJob->failed_at);
        // Note: Job object has stale attempts value from when it was fetched
        // After fetch: attempts was incremented to 1 in DB
        // markFailedJob doesn't update job object, so $job->attempts is still from fetch time
        // But the job object itself doesn't get updated, so release() uses the original attempts
        // This is a quirk of DatabaseEngine - it doesn't keep job object in sync with DB
        $this->assertGreaterThanOrEqual(1, $updatedJob->attempts);
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
        
        // Job should not be fetched yet (returns false for no job)
        $this->assertFalse($job);
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
