<?php

namespace Lightpack\Jobs\Engines;

use Throwable;
use Lightpack\Utils\Moment;
use Lightpack\Jobs\BaseEngine;
use Lightpack\Redis\Redis;

class RedisEngine extends BaseEngine
{
    /**
     * Redis client instance
     */
    protected Redis $redis;
    
    /**
     * Key prefix for Redis
     */
    protected string $prefix;
    
    /**
     * Constructor
     */
    public function __construct(Redis $redis, string $prefix = 'jobs:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    
    /**
     * Add a job to the queue
     */
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void
    {
        $job = [
            'id' => $this->generateJobId(),
            'handler' => $jobHandler,
            'payload' => json_encode($payload),
            'scheduled_at' => (new Moment)->travel($delay),
            'status' => 'new',
            'queue' => $queue,
            'attempts' => 0,
            'created_at' => (new Moment)->now(),
        ];
        
        // Store the job data
        $this->redis->set($this->getJobKey($job['id']), $job);
        
        // Add to the queue set
        $this->redis->zAdd(
            $this->getQueueKey($queue), 
            $this->getTimestamp($job['scheduled_at']),
            $job['id']
        );
    }
    
    /**
     * Fetch the next job from the queue
     */
    public function fetchNextJob(?string $queue = null)
    {
        $now = time();
        $queues = $queue ? [$queue] : $this->getQueues();
        
        foreach ($queues as $queueName) {
            $queueKey = $this->getQueueKey($queueName);
            
            // Get jobs scheduled before or at current time
            $jobIds = $this->redis->zRangeByScore(
                $queueKey,
                0,
                $now,
                ['limit' => [0, 1]]
            );
            
            if (empty($jobIds)) {
                continue;
            }
            
            $jobId = $jobIds[0];
            $jobKey = $this->getJobKey($jobId);
            
            // Use Redis transaction to ensure atomicity
            $this->redis->watch($queueKey);
            
            // Check if job still exists in queue (could have been taken by another worker)
            $score = $this->redis->zScore($queueKey, $jobId);
            if ($score === false) {
                $this->redis->unwatch();
                continue;
            }
            
            // Start transaction
            $tx = $this->redis->multi();
            
            // Remove from queue
            $tx->zRem($queueKey, $jobId);
            
            // Execute transaction
            $result = $tx->exec();
            
            // If transaction failed (another worker took the job), try next queue
            if ($result === false || $result[0] === 0) {
                continue;
            }
            
            // Get the job data
            $job = $this->redis->get($jobKey);
            
            if (!$job) {
                continue;
            }
            
            // Update job status and attempts
            $job['status'] = 'queued';
            $job['attempts'] = $job['attempts'] + 1;
            $job['updated_at'] = (new Moment)->now();
            
            // Save updated job
            $this->redis->set($jobKey, $job);
            
            // Convert to object for compatibility with the interface
            $jobObject = (object) $job;
            $this->deserializePayload($jobObject);
            
            return $jobObject;
        }
        
        return null;
    }
    
    /**
     * Delete a job
     */
    public function deleteJob($job): void
    {
        // Remove from job storage
        $this->redis->delete($this->getJobKey($job->id));
        
        // Remove from failed jobs if it exists there
        $this->redis->zRem($this->getFailedQueueKey(), $job->id);
    }
    
    /**
     * Mark a job as failed
     */
    public function markFailedJob($job, Throwable $e): void
    {
        // Get current job data
        $jobData = $this->redis->get($this->getJobKey($job->id));
        
        if (!$jobData) {
            return;
        }
        
        // Update job data
        $jobData['status'] = 'failed';
        $jobData['exception'] = (string) $e;
        $jobData['failed_at'] = (new Moment)->now();
        
        // Save updated job
        $this->redis->set($this->getJobKey($job->id), $jobData);
        
        // Add to failed queue
        $this->redis->zAdd(
            $this->getFailedQueueKey(),
            time(),
            $job->id
        );
        
        // Update the job object for consistency
        $job->status = 'failed';
        $job->exception = (string) $e;
        $job->failed_at = $jobData['failed_at'];
    }
    
    /**
     * Release a job back to the queue
     */
    public function release($job, string $delay = 'now'): void
    {
        // Get current job data
        $jobData = $this->redis->get($this->getJobKey($job->id));
        
        if (!$jobData) {
            return;
        }
        
        // Update job data
        $jobData['status'] = 'new';
        $jobData['exception'] = null;
        $jobData['failed_at'] = null;
        $jobData['scheduled_at'] = (new Moment)->travel($delay);
        $jobData['attempts'] = $jobData['attempts'] + 1;
        
        // Save updated job
        $this->redis->set($this->getJobKey($job->id), $jobData);
        
        // Add back to queue
        $this->redis->zAdd(
            $this->getQueueKey($job->queue),
            $this->getTimestamp($jobData['scheduled_at']),
            $job->id
        );
        
        // Remove from failed queue if it was there
        $this->redis->zRem($this->getFailedQueueKey(), $job->id);
        
        // Update the job object for consistency
        $job->status = 'new';
        $job->exception = null;
        $job->failed_at = null;
        $job->scheduled_at = $jobData['scheduled_at'];
        $job->attempts = $jobData['attempts'];
    }

    /**
     * Get all available queues
     */
    protected function getQueues(): array
    {
        $keys = $this->redis->keys($this->prefix . 'queue:*');
        $queues = [];
        
        foreach ($keys as $key) {
            $parts = explode(':', $key);
            $queues[] = end($parts);
        }
        
        return $queues;
    }
    
    /**
     * Generate a unique job ID
     */
    protected function generateJobId(): string
    {
        return uniqid('job_', true);
    }
    
    /**
     * Get Redis key for a job
     */
    protected function getJobKey(string $jobId): string
    {
        return $this->prefix . 'job:' . $jobId;
    }
    
    /**
     * Get Redis key for a queue
     */
    protected function getQueueKey(string $queue): string
    {
        return $this->prefix . 'queue:' . $queue;
    }
    
    /**
     * Get Redis key for failed jobs
     */
    protected function getFailedQueueKey(): string
    {
        return $this->prefix . 'failed';
    }
    
    /**
     * Convert date string to timestamp
     */
    protected function getTimestamp(string $date): int
    {
        return strtotime($date);
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs($jobId = null): int
    {
        $failedKey = $this->getFailedQueueKey();
        
        if ($jobId !== null) {
            // Retry specific job
            $jobKey = $this->getJobKey($jobId);
            $job = $this->redis->get($jobKey);
            
            if (!$job || $job['status'] !== 'failed') {
                return 0;
            }
            
            // Reset job data
            $job['status'] = 'new';
            $job['attempts'] = 0;
            $job['exception'] = null;
            $job['failed_at'] = null;
            $job['scheduled_at'] = (new Moment)->now();
            
            // Save updated job
            $this->redis->set($jobKey, $job);
            
            // Remove from failed queue
            $this->redis->zRem($failedKey, $jobId);
            
            // Add back to queue
            $queueKey = $this->getQueueKey($job['queue']);
            $this->redis->zAdd($queueKey, time(), $jobId);
            
            return 1;
        }
        
        // Retry all failed jobs
        $failedJobIds = $this->redis->zRange($failedKey, 0, -1);
        $count = 0;
        
        foreach ($failedJobIds as $id) {
            $count += $this->retryFailedJobs($id);
        }
        
        return $count;
    }
}
