<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;

class NullEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay)
    {
        // Do nothing
    }

    public function fetchNextJob()
    {
        return null;
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
