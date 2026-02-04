<?php

namespace Lightpack\Mfa;

use Lightpack\Container\Container;
use RobThree\Auth\TwoFactorAuth;

class TotpSetupHelper
{
    public static function getTotpInstance(): TwoFactorAuth
    {
        // No QR provider - we handle QR generation separately
        return new TwoFactorAuth(
            issuer: self::getIssuerName()
        );
    }

    public static function generateSecret(): string
    {
        $tfa = self::getTotpInstance();
        return $tfa->createSecret();
    }

    /**
     * Get the otpauth:// URI for TOTP setup
     * This is the single source of truth for QR code generation
     * 
     * @param string $secret The TOTP secret
     * @param string $userEmail User's email address
     * @return string The otpauth:// URI
     */
    public static function getOtpAuthUri(string $secret, string $userEmail): string
    {
        $issuer = urlencode(self::getIssuerName());
        $email = urlencode($userEmail);
        $label = "{$issuer}:{$email}";
        
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generate QR code as SVG (for PDFs, emails, reports)
     * Requires: composer require bacon/bacon-qr-code
     * 
     * @param string $secret The TOTP secret
     * @param string $userEmail User's email address
     * @param int $size QR code size in pixels (default: 256)
     * @return string SVG markup
     * @throws \RuntimeException if bacon/bacon-qr-code is not installed
     */
    public static function getQrCodeSvg(string $secret, string $userEmail, int $size = 256): string
    {
        if (!class_exists('BaconQrCode\\Writer')) {
            throw new \RuntimeException(
                'bacon/bacon-qr-code is required for server-side QR generation. ' .
                'Install it with: composer require bacon/bacon-qr-code'
            );
        }

        $otpAuthUri = self::getOtpAuthUri($secret, $userEmail);
        
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        
        $writer = new \BaconQrCode\Writer($renderer);
        return $writer->writeString($otpAuthUri);
    }

    /**
     * Generate QR code as PNG data URI (for inline embedding)
     * Requires: composer require bacon/bacon-qr-code
     * 
     * @param string $secret The TOTP secret
     * @param string $userEmail User's email address
     * @param int $size QR code size in pixels (default: 256)
     * @return string Data URI (data:image/png;base64,...)
     * @throws \RuntimeException if bacon/bacon-qr-code is not installed
     */
    public static function getQrCodeDataUri(string $secret, string $userEmail, int $size = 256): string
    {
        if (!class_exists('BaconQrCode\\Writer')) {
            throw new \RuntimeException(
                'bacon/bacon-qr-code is required for server-side QR generation. ' .
                'Install it with: composer require bacon/bacon-qr-code'
            );
        }

        $otpAuthUri = self::getOtpAuthUri($secret, $userEmail);
        
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size),
            new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
        );
        
        $writer = new \BaconQrCode\Writer($renderer);
        $pngData = $writer->writeString($otpAuthUri);
        
        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    /**
     * @deprecated Use getOtpAuthUri() for client-side or getQrCodeSvg() for server-side
     */
    public static function getQrUri(string $secret, string $userEmail): string
    {
        return self::getOtpAuthUri($secret, $userEmail);
    }

    public static function getIssuerName(): string
    {
        return Container::getInstance()
            ->get('config')
            ->get('app.name', 'Lightpack App');
    }
}
