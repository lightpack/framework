<?php

namespace Lightpack\Mfa\Drivers;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Cache\Cache;
use Lightpack\Sms\Sms;
use Lightpack\Config\Config;
use Lightpack\Mfa\Job\SmsMfaJob;
use Lightpack\Utils\Otp;

/**
 * SMS-based MFA driver implementation.
 */
class SmsDriver extends BaseDriver
{
    public function __construct(
        Cache $cache,
        Config $config,
        Otp $otp,
        protected Sms $sms,
    ) {
        parent::__construct($cache, $config, $otp);
    }

    protected function getType(): string
    {
        return 'sms';
    }

    protected function doSend(AuthUser $user, string $code): void
    {
        $message = $this->config->get('mfa.sms.message', 'Your verification code is: {code}');
        $message = str_replace('{code}', $code, $message);

        (new SmsMfaJob)->dispatch([
            'phone' => $user->phone,
            'message' => $message,
        ]);
    }

}

