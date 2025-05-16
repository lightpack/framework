<?php

namespace Lightpack\Mfa\Job;

use Lightpack\Jobs\Job;

class SmsMfaJob extends Job
{
    public function onQueue(): string
    {
        return config('mfa.sms.queue', 'default');
    }

    public function maxAttempts(): int
    {
        return (int) config('mfa.sms.max_attempts', 1);
    }

    public function retryAfter(): string
    {
        return config('mfa.sms.retry_after', '60 seconds');
    }

    public function run(): void
    {
        app('sms')->send(
            $this->payload['phone'],
            $this->payload['message'],
            $this->payload['options'] ?? []
        );
    }
}
