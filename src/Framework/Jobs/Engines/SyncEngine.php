<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Throwable;

class SyncEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue)
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

    public function deleteJob($job)
    {
        // Do nothing
    }

    public function markFailedJob($job, Throwable $e)
    {
        // Do nothing
    }

    public function release($job, string $delay = 'now')
    {
        // Do nothing
    }
}
