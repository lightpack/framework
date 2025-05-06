<?php

namespace Lightpack\Captcha;

use PHPUnit\Framework\TestCase;

class NullCaptchaTest extends TestCase
{
    public function testGenerateReturnsEmptyString()
    {
        $captcha = new NullCaptcha();
        $this->assertSame('', $captcha->generate());
    }

    public function testVerifyAlwaysReturnsTrue()
    {
        $captcha = new NullCaptcha();
        $this->assertTrue($captcha->verify('any-value'));
    }
}
