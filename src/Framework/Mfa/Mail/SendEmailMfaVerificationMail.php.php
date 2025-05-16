<?php

namespace Lightpack\Mfa\Mail;

use Lightpack\Mail\Mail;

class SendEmailMfaVerificationMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['user']['email'])
            ->subject('Your MFA Code')
            ->body('Your verification code is: ' . $payload['mfa_code'])
            ->send();
    }
}
