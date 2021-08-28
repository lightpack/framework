<?php

namespace Lightpack\Jobs;

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
        string $delay
    );

    /**
     * Find next job to process.
     *
     * @return object|null
     */
    abstract public function fetchNextJob();

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
    abstract public function markFailedJob($job);

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
