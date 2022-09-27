<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;

class SyncEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay)
    {
        /** @var \Lightpack\Jobs\Job $job */
        $job = app($jobHandler);
        $job->setPayload($payload);

        app()->call($job, 'run');
    }  

    public function fetchNextJob()
    {
        // Do nothing
    }

    public function deleteJob($job)
    {
        // Do nothing
    }

    public function markFailedJob($job)
    {
        // Do nothing
    }
}
