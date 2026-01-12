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
                
                // Increment attempts when fetching (like real engines)
                $job['attempts']++;
                
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
        // Don't increment attempts here - fetchNextJob() already did it
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

    public function retryFailedJobs($jobId = null, ?string $queue = null): int
    {
        // Mock implementation for testing
        if ($jobId !== null) {
            // Retry specific job
            foreach ($this->failedJobs as $index => $failedJob) {
                if ($failedJob['job']->id === $jobId) {
                    $this->jobs[] = (array) $failedJob['job'];
                    unset($this->failedJobs[$index]);
                    $this->failedJobs = array_values($this->failedJobs);
                    return 1;
                }
            }
            return 0;
        }
        
        // Retry all failed jobs (optionally filtered by queue)
        $count = 0;
        foreach ($this->failedJobs as $index => $failedJob) {
            // If queue filter is specified, check if job belongs to that queue
            if ($queue !== null && $failedJob['job']->queue !== $queue) {
                continue;
            }
            
            $this->jobs[] = (array) $failedJob['job'];
            unset($this->failedJobs[$index]);
            $count++;
        }
        $this->failedJobs = array_values($this->failedJobs);
        return $count;
    }
}
