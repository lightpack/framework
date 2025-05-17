<?php
namespace Lightpack\Mfa\Factor;

use Lightpack\Mfa\MfaInterface;
use Lightpack\Auth\Models\AuthUser;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

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
        $secret = $user->mfa_totp_secret;

        if (!$input || empty($secret)) {
            return false;
        }
        $qrcodeProvider = new QRServerProvider();
        $tfa = new TwoFactorAuth($qrcodeProvider, 'LightpackApp');
        return $tfa->verifyCode($secret, $input);


    }

    public function getName(): string
    {
        return 'totp';
    }
}
