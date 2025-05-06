<?php

namespace Lightpack\Captcha;

use PHPUnit\Framework\TestCase;

class GoogleReCaptchaTest extends TestCase
{
    public function testGenerateReturnsHtmlWithSiteKey()
    {
        $captcha = new GoogleReCaptcha('site-key', 'secret-key');
        $html = $captcha->generate();
        $this->assertStringContainsString('g-recaptcha', $html);
        $this->assertStringContainsString('site-key', $html);
    }

    public function testVerifyReturnsFalseIfInputIsEmpty()
    {
        $captcha = new GoogleReCaptcha('site-key', 'secret-key');
        $this->assertFalse($captcha->verify(''));
    }

    public function testVerifyReturnsFalseIfApiFails()
    {
        // Temporarily override fetchVerifyResponse
        $captcha = $this->getMockBuilder(GoogleReCaptcha::class)
            ->setConstructorArgs(['site-key', 'secret-key'])
            ->onlyMethods(['fetchVerifyResponse'])
            ->getMock();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => false]));
        $this->assertFalse($captcha->verify('dummy-token'));
    }

    public function testVerifyReturnsTrueIfApiSucceeds()
    {
        $captcha = $this->getMockBuilder(GoogleReCaptcha::class)
            ->setConstructorArgs(['site-key', 'secret-key'])
            ->onlyMethods(['fetchVerifyResponse'])
            ->getMock();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => true]));
        $this->assertTrue($captcha->verify('dummy-token'));
    }
}
