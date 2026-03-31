<?php
namespace Lightpack\Mfa\Drivers;

use Lightpack\Mfa\MfaInterface;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Mfa\TotpSetupHelper;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

/**
 * MFA Driver for TOTP (Authenticator Apps)
 */
class TotpDriver implements MfaInterface
{
    public function send(AuthUser $user): void
    {
        // No code to send; user generates code in their authenticator app.
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        $secret = $user->mfa_totp_secret;

        if (!$input || empty($secret)) {
            return false;
        }
        
        $tfa = TotpSetupHelper::getTotpInstance();
        return $tfa->verifyCode($secret, $input);


    }

    public function getName(): string
    {
        return 'totp';
    }
}
