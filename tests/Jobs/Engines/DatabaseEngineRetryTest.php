<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Container\Container;

final class DatabaseEngineRetryTest extends TestCase
{
    private $db;
    
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
        
        // Add a failed job
        $this->db->query("INSERT INTO jobs (handler, payload, queue, status, attempts, exception, failed_at, scheduled_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
            'TestJob',
            json_encode(['key' => 'value']),
            'default',
            'failed',
            3,
            'Test exception',
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ]);
        
        $jobId = $this->db->lastInsertId();
        
        // Retry the job
        $engine = new DatabaseEngine();
        $count = $engine->retryFailedJobs($jobId);
        
        $this->assertEquals(1, $count);
        
        // Verify job was reset
        $job = $this->db->query("SELECT * FROM jobs WHERE id = ?", [$jobId])->fetch(\PDO::FETCH_OBJ);
        
        $this->assertEquals('new', $job->status);
        $this->assertEquals(0, $job->attempts);
        $this->assertNull($job->exception);
        $this->assertNull($job->failed_at);
    }
    
    public function testCanRetryAllFailedJobs()
    {
        if (!$this->db) {
            $this->markTestSkipped('Database not available');
        }
        
        // Clean up
        $this->db->query("DELETE FROM jobs");
        
        // Add multiple failed jobs
        for ($i = 0; $i < 3; $i++) {
            $this->db->query("INSERT INTO jobs (handler, payload, queue, status, attempts, exception, failed_at, scheduled_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
                'TestJob',
                json_encode(['key' => 'value']),
                'default',
                'failed',
                3,
                'Test exception',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]);
        }
        
        // Retry all failed jobs
        $engine = new DatabaseEngine();
        $count = $engine->retryFailedJobs();
        
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
        
        $engine = new DatabaseEngine();
        $count = $engine->retryFailedJobs(99999);
        
        $this->assertEquals(0, $count);
    }
}
