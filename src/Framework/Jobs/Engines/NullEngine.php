<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Throwable;

class NullEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue)
    {
        // Do nothing
    }

    public function fetchNextJob(?string $queue = null)
    {
        return null;
    }

    public function deleteJob($job)
    {
        // Do nothing
    }

    public function markFailedJob($job, Throwable $e)
    {
        // Do nothing
    }
}
