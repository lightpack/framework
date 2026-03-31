<?php

namespace Lightpack\Mfa\Drivers;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Mfa\Job\EmailMfaJob;
use Lightpack\Utils\Limiter;
use Lightpack\Utils\Otp;

/**
 * Email-based MFA driver implementation.
 */
class EmailDriver extends BaseDriver
{
    public function __construct(
        Cache $cache,
        Config $config,
        Otp $otp,
    ) {
        parent::__construct($cache, $config, $otp);
    }

    protected function getType(): string
    {
        return 'email';
    }

    protected function doSend(AuthUser $user, string $code): void
    {
        (new EmailMfaJob)->dispatch([
            'user' => $user->toArray(),
            'mfa_code' => $code,
        ]);
    }

}
