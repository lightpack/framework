<?php

namespace Lightpack\Mfa;

use Lightpack\Container\Container;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class TotpSetupHelper
{
    public static function getTotpInstance(): TwoFactorAuth
    {
        return new TwoFactorAuth(
            issuer: self::getIssuerName(),
            qrcodeprovider: new QRServerProvider()
        );
    }

    public static function generateSecret(): string
    {
        $tfa = self::getTotpInstance();
        return $tfa->createSecret();
    }

    public static function getQrUri(string $secret, string $userEmail): string
    {
        $tfa = self::getTotpInstance();

        // Returns a Data URI for QR image
        return $tfa->getQRCodeImageAsDataUri($userEmail, $secret);
    }

    public static function getIssuerName(): string
    {
        return Container::getInstance()
            ->get('config')
            ->get('app.name', 'Lightpack App');
    }
}
