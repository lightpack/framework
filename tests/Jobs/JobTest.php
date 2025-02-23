<?php

namespace Lightpack\Tests\Jobs;

use PHPUnit\Framework\TestCase;
use Lightpack\Jobs\Job;
use Throwable;

class TestJob extends Job
{
    // Test job class for running our tests
}

class JobTest extends TestCase
{
    private TestJob $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new TestJob();
    }

    public function testJobCanSetAndGetPayload()
    {
        $payload = ['key' => 'value'];
        
        $this->job->setPayload($payload);
        
        $this->assertEquals($payload, $this->job->getPayload());
    }

    public function testJobCanSetAndGetException()
    {
        $exception = new \Exception('Test exception');
        
        $this->job->setException($exception);
        
        $this->assertSame($exception, $this->job->getException());
    }

    public function testJobDefaultQueue()
    {
        // Access protected property through reflection
        $reflection = new \ReflectionClass($this->job);
        $property = $reflection->getProperty('queue');
        $property->setAccessible(true);
        
        $this->assertEquals('default', $property->getValue($this->job));
    }

    public function testJobDefaultDelay()
    {
        $reflection = new \ReflectionClass($this->job);
        $property = $reflection->getProperty('delay');
        $property->setAccessible(true);
        
        $this->assertEquals('now', $property->getValue($this->job));
    }

    public function testJobDefaultAttempts()
    {
        $reflection = new \ReflectionClass($this->job);
        $property = $reflection->getProperty('attempts');
        $property->setAccessible(true);
        
        $this->assertEquals(1, $property->getValue($this->job));
    }

    public function testJobDefaultRetryAfter()
    {
        $reflection = new \ReflectionClass($this->job);
        $property = $reflection->getProperty('retryAfter');
        $property->setAccessible(true);
        
        $this->assertEquals('now', $property->getValue($this->job));
    }
}
