<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Throwable;

class NullEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void
    {
        // Do nothing
    }

    public function fetchNextJob(?string $queue = null)
    {
        return null;
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
        // Do nothing
    }

    public function retryFailedJobs($jobId = null, ?string $queue = null): int
    {
        // No-op: Null engine doesn't persist failed jobs
        return 0;
    }
}
