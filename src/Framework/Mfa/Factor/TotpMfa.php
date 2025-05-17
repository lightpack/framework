<?php
namespace Lightpack\Mfa\Factor;

use Lightpack\Mfa\MfaInterface;
use Lightpack\Auth\Models\AuthUser;
use OTPHP\TOTP;

/**
 * MFA Factor for TOTP (Authenticator Apps)
 */
class TotpMfa implements MfaInterface
{
    public function send(AuthUser $user): void
    {
        // No code to send; user generates code in their authenticator app.
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        if (!$input || empty($user->mfa_totp_secret)) {
            return false;
        }
        $totp = TOTP::create($user->mfa_totp_secret);
        return $totp->verify($input);
    }

    public function getName(): string
    {
        return 'totp';
    }
}
