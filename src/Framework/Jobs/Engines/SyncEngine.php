<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Throwable;

class SyncEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void
    {
        /** @var \Lightpack\Jobs\Job $job */
        $job = app($jobHandler);
        $job->setPayload($payload);

        app()->call($job, 'run');
    }  

    public function fetchNextJob(?string $queue = null)
    {
        // Do nothing
    }

    public function deleteJob($job): void
    {
        // Do nothing
    }

    public function markFailedJob($job, Throwable $e): void
    {
        // Do nothing
    }

    public function release($job, string $delay = 'now'): void
    {
        // No-op: Sync engine doesn't queue jobs
    }

    public function retryFailedJobs($jobId = null, ?string $queue = null): int
    {
        // No-op: Sync engine doesn't persist failed jobs
        return 0;
    }
}
