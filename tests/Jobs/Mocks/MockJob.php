<?php

namespace Lightpack\Tests\Jobs\Mocks;

use Lightpack\Jobs\Job;

class MockJob extends Job
{
    public function run()
    {
        // Test job implementation
    }

    public function onSuccess()
    {
        // Success callback
    }
}
