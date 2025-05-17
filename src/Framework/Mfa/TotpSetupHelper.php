<?php
namespace Lightpack\Mfa;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class TotpSetupHelper
{
    public static function generateSecret(): string
    {
        $qrcodeProvider = new QRServerProvider();
        $tfa = new TwoFactorAuth($qrcodeProvider, 'LightpackApp');
        return $tfa->createSecret();
    }

    public static function getQrUri(string $secret, string $userEmail, string $appName): string
    {
        $qrcodeProvider = new QRServerProvider();
        $tfa = new TwoFactorAuth($qrcodeProvider, $appName);
        // Returns a Data URI for QR image
        return $tfa->getQRCodeImageAsDataUri($userEmail, $secret);

    }
}
