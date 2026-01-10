<?php

namespace Lightpack\Tests\Jobs\Mocks;

use Lightpack\Jobs\BaseEngine;

class MockJobEngine extends BaseEngine
{
    private $jobs = [];
    private $processedJobs = [];
    private $failedJobs = [];

    public function addJob(string $jobHandler, array $payload = [], string $delay = 'now', string $queue = 'default', int $attempts = 0): void
    {
        $this->jobs[] = [
            'id' => uniqid(),
            'handler' => $jobHandler,
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

    public function releaseWithoutIncrement($job, string $delay = 'now'): void
    {
        $job = (array) $job;
        $job['delay'] = $delay;
        // Note: attempts is NOT incremented
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
