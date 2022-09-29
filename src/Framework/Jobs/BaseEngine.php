<?php

namespace Lightpack\Jobs;

use Throwable;

abstract class BaseEngine
{
    /**
     * Add a job in the queue.
     *
     * @param string $jobHandler    Job classname.
     * @param array $payload        Job payload data.  
     * @param string $delay         Delay the job with 'strtotime' compatible value.
     * @return void
     */
    abstract public function addJob(
        string $jobHandler,
        array $payload,
        string $delay,
        string $queue
    );

    /**
     * Find next job to process.
     *
     * @return object|null
     */
    abstract public function fetchNextJob(?string $queue = null);

    /**
     * Delete the provided job.
     *
     * @param object $job
     * @return void
     */
    abstract public function deleteJob($job);

    /**
     * Mark the job as failed.
     *
     * @param object $job
     * @return void
     */
    abstract public function markFailedJob($job, Throwable $e);

    /**
     * Deserialize the job payload as an array.
     *
     * @param object $job
     * @return void
     */
    protected function deserializePayload($job)
    {
        $job->payload = json_decode($job->payload, true);
    }
}
