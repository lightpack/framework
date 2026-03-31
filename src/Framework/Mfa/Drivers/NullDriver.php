<?php

namespace Lightpack\Mfa\Drivers;

use Lightpack\Mfa\MfaInterface;

/**
 * Null MFA driver for testing or when MFA is disabled.
 */
class NullDriver implements MfaInterface
{
    public function send($user): void
    {
        // No-op
    }

    public function validate($user, $input): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'null';
    }
}
