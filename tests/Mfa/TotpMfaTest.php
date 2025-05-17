<?php

namespace Lightpack\Tests\Mfa;

use PHPUnit\Framework\TestCase;
use Lightpack\Mfa\Factor\TotpMfa;
use Lightpack\Auth\Models\AuthUser;
use RobThree\Auth\TwoFactorAuth;

class TotpMfaTest extends TestCase
{
    protected $user;
    protected $factor;
    protected $secret;
    protected $tfa;

    protected function setUp(): void
    {
        $this->factor = new TotpMfa();
        $this->user = $this->getMockBuilder(AuthUser::class)
            ->onlyMethods(['save'])
            ->getMock();
        $this->secret = 'JBSWY3DPEHPK3PXP'; // Example base32 secret
        $this->user->mfa_totp_secret = $this->secret;
        $qrcodeProvider = new \RobThree\Auth\Providers\Qr\QRServerProvider();
        $this->tfa = new TwoFactorAuth($qrcodeProvider, 'LightpackApp');
    }

    public function testValidateReturnsFalseIfNoInput()
    {
        $this->assertFalse($this->factor->validate($this->user, null));
        $this->assertFalse($this->factor->validate($this->user, ''));
    }

    public function testValidateReturnsFalseIfNoSecret()
    {
        $this->user->mfa_totp_secret = null;
        $code = $this->tfa->getCode('JBSWY3DPEHPK3PXP');
        $this->assertFalse($this->factor->validate($this->user, $code));
    }

    public function testValidateReturnsFalseForInvalidCode()
    {
        $this->assertFalse($this->factor->validate($this->user, '123456'));
    }

    public function testValidateReturnsTrueForValidCode()
    {
        $code = $this->tfa->getCode($this->secret);
        $this->assertTrue($this->factor->validate($this->user, $code));
    }

    public function testGetName()
    {
        $this->assertSame('totp', $this->factor->getName());
    }
}
