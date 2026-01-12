<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Container\Container;

final class DatabaseEngineRetryTest extends TestCase
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
        
        // Create engine instance
        $this->engine = new DatabaseEngine();
    }
    
    protected function tearDown(): void
    {
        if ($this->db) {
            // Clean up test data
            $this->db->query("DELETE FROM jobs");
        }
        
        parent::tearDown();
    }
    
    public function testCanRetrySpecificFailedJob()
    {
        if (!$this->db) {
            $this->markTestSkipped('Database not available');
        }
        
        // Clean up
        $this->db->query("DELETE FROM jobs");
        
        // Add a job using the engine
        $this->engine->addJob('TestJob', ['key' => 'value'], 'now', 'default');
        
        // Fetch and mark it as failed
        $job = $this->engine->fetchNextJob();
        $exception = new \Exception('Test exception');
        $this->engine->markFailedJob($job, $exception);
        
        // Retry the failed job
        $count = $this->engine->retryFailedJobs($job->id);
        
        $this->assertEquals(1, $count);
        
        // Verify job was reset
        $updatedJob = $this->db->query("SELECT * FROM jobs WHERE id = ?", [$job->id])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertEquals('new', $updatedJob->status);
        $this->assertEquals(0, $updatedJob->attempts);
        $this->assertNull($updatedJob->exception);
        $this->assertNull($updatedJob->failed_at);
    }
    
    public function testCanRetryAllFailedJobs()
    {
        if (!$this->db) {
            $this->markTestSkipped('Database not available');
        }
        
        // Clean up
        $this->db->query("DELETE FROM jobs");
        
        // Add multiple jobs using the engine and mark them as failed
        for ($i = 0; $i < 3; $i++) {
            $this->engine->addJob('TestJob', ['key' => 'value' . $i], 'now', 'default');
            $job = $this->engine->fetchNextJob();
            $exception = new \Exception('Test exception');
            $this->engine->markFailedJob($job, $exception);
        }
        
        // Retry all failed jobs
        $count = $this->engine->retryFailedJobs();
        
        $this->assertEquals(3, $count);
        
        // Verify all jobs were reset
        $jobs = $this->db->query("SELECT * FROM jobs WHERE status = 'new'")->fetchAll(\PDO::FETCH_OBJ);
        
        $this->assertCount(3, $jobs);
        
        foreach ($jobs as $job) {
            $this->assertEquals(0, $job->attempts);
            $this->assertNull($job->exception);
            $this->assertNull($job->failed_at);
        }
    }
    
    public function testReturnsZeroForNonExistentJob()
    {
        if (!$this->db) {
            $this->markTestSkipped('Database not available');
        }
        
        // Clean up
        $this->db->query("DELETE FROM jobs");
        
        $count = $this->engine->retryFailedJobs(99999);
        
        $this->assertEquals(0, $count);
    }
}
