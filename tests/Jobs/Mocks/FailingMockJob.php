<?php

namespace Lightpack\Tests\Jobs\Mocks;

use Lightpack\Jobs\Job;

class FailingMockJob extends Job
{
    private $shouldFail;
    public $attempts = 0;  // Made public for testing

    public function __construct(bool $shouldFail = true)
    {
        $this->shouldFail = $shouldFail;
    }

    public function run()
    {
        if ($this->shouldFail) {
            throw new \RuntimeException("Job failed on attempt {$this->attempts}");
        }
    }

    public function onFailure()
    {
        // Failure callback - exception is available via $this->exception
    }

    public function maxAttempts(): int
    {
        return 3;
    }

    public function retryAfter(): string
    {
        return '+5 seconds';
    }
}
