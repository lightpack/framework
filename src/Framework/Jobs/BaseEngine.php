<?php

namespace Lightpack\Jobs;

use Throwable;
use Lightpack\Jobs\Job;

abstract class BaseEngine
{
    /**
     * Add a job in the queue.
     *
     * @param string $jobHandler    Job classname.
     * @param array $payload        Job payload data.  
     * @param string $delay         Delay the job with 'strtotime' compatible value.
     * @param string $queue         Queue name.
     * 
     * @return void
     */
    abstract public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void;

    /**
     * Find next job to process.
     *
     * @return object|null
     */
    abstract public function fetchNextJob(?string $queue = null);

    /**
     * Delete the provided job.
     *
     * @param obj $job
     */
    abstract public function deleteJob($job): void;

    /**
     * Mark the job as failed.
     *
     * @param obj
     */
    abstract public function markFailedJob($job, Throwable $e): void;

    /**
     * Release the job back into the queue.
     *
     * @param obj $job
     * @param string $delay
     */
    abstract public function release($job, string $delay = 'now'): void;

    /**
     * Deserialize the job payload as an array.
     *
     * @param obj $job
     * @return void
     */
    protected function deserializePayload($job)
    {
        $job->payload = json_decode($job->payload, true);
    }
}
