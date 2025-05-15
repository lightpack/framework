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

    protected function getMailer(): Mail
    {
        $mailer = config('mfa.email.mailer');

        if (!$mailer) {
            return (new Mail)
                ->to($this->payload['user']['email'])
                ->subject('Your MFA Code')
                ->body('Your verification code is: ' . $this->payload['mfa_code']);
        }

        return new $mailer;
    }

    public function run(): void
    {
        $this->getMailer()->send();
    }
}
