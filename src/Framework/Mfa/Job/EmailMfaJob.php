<?php

namespace Lightpack\Mfa\Job;

use Lightpack\Jobs\Job;
use Lightpack\Mail\Mail;

class EmailMfaJob extends Job
{
    public function onQueue(): string
    {
        return config('mfa.email.queue', 'default');
    }

    public function maxAttempts(): int
    {
        return (int) config('mfa.email.max_attempts', 1);
    }

    public function retryAfter(): string
    {
        return config('mfa.email.retry_after', '60 seconds');
    }

    public function run(): void
    {
        $mail = config('mfa.email.mailer');

        (new $mail)->dispatch([
            $this->payload
        ]);
    }
}
