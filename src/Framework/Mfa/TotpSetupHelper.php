<?php
namespace Lightpack\Mfa;

use OTPHP\TOTP;

class TotpSetupHelper
{
    public static function generateSecret(): string
    {
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    public static function getQrUri(string $secret, string $userEmail, string $appName): string
    {
        $totp = TOTP::create($secret);
        return $totp->getProvisioningUri($userEmail, $appName);
    }
}
