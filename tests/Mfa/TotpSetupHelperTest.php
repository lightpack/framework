<?php

namespace Lightpack\Tests\Mfa;

use PHPUnit\Framework\TestCase;
use Lightpack\Mfa\TotpSetupHelper;
use Lightpack\Container\Container;

class TotpSetupHelperTest extends TestCase
{
    protected function setUp(): void
    {
        Container::getInstance()->register('config', function() {
            return new class {
                public function get($key, $default = null) {
                    if ($key === 'app.name') {
                        return 'Test App';
                    }
                    return $default;
                }
            };
        });
    }

    public function testGenerateSecretReturnsValidBase32String()
    {
        $secret = TotpSetupHelper::generateSecret();
        
        $this->assertIsString($secret);
        $this->assertGreaterThan(0, strlen($secret));
        // Base32 should only contain A-Z and 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGetOtpAuthUriReturnsValidUri()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $email = 'user@example.com';
        
        $uri = TotpSetupHelper::getOtpAuthUri($secret, $email);
        
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString($secret, $uri);
        $this->assertStringContainsString('Test+App', $uri); // URL encoded
        $this->assertStringContainsString('user%40example.com', $uri); // URL encoded
        $this->assertStringContainsString('algorithm=SHA1', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    public function testGetOtpAuthUriHandlesSpecialCharacters()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $email = 'user+test@example.com';
        
        $uri = TotpSetupHelper::getOtpAuthUri($secret, $email);
        
        // Should be URL encoded
        $this->assertStringContainsString('user%2Btest%40example.com', $uri);
    }

    public function testGetQrCodeSvgReturnsValidSvg()
    {
        if (!class_exists('BaconQrCode\\Writer')) {
            $this->markTestSkipped('bacon/bacon-qr-code not installed');
        }

        $secret = 'JBSWY3DPEHPK3PXP';
        $email = 'user@example.com';
        
        $svg = TotpSetupHelper::getQrCodeSvg($secret, $email, 256);
        
        $this->assertIsString($svg);
        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function testGetQrCodeDataUriReturnsValidDataUri()
    {
        if (!class_exists('BaconQrCode\\Writer')) {
            $this->markTestSkipped('bacon/bacon-qr-code not installed');
        }

        $secret = 'JBSWY3DPEHPK3PXP';
        $email = 'user@example.com';
        
        $dataUri = TotpSetupHelper::getQrCodeDataUri($secret, $email, 256);
        
        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        
        // Verify it's valid base64
        $base64Data = substr($dataUri, strlen('data:image/png;base64,'));
        $decoded = base64_decode($base64Data, true);
        $this->assertNotFalse($decoded);
    }

    public function testGetIssuerNameReturnsConfiguredAppName()
    {
        $issuer = TotpSetupHelper::getIssuerName();
        
        $this->assertSame('Test App', $issuer);
    }

    public function testGetTotpInstanceReturnsValidTwoFactorAuthInstance()
    {
        $tfa = TotpSetupHelper::getTotpInstance();
        
        $this->assertInstanceOf(\RobThree\Auth\TwoFactorAuth::class, $tfa);
    }
}
