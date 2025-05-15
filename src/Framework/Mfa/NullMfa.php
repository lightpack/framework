<?php

namespace Lightpack\Mfa;

/**
 * Null MFA factor for testing or when MFA is disabled.
 */
class NullMfa implements MfaInterface
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
