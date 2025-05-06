<?php

namespace Lightpack\Captcha;

use PHPUnit\Framework\TestCase;

class CloudflareTurnstileTest extends TestCase
{
    public function testGenerateReturnsHtmlWithSiteKey()
    {
        $captcha = new CloudflareTurnstile('site-key', 'secret-key');
        $html = $captcha->generate();
        $this->assertStringContainsString('cf-turnstile', $html);
        $this->assertStringContainsString('site-key', $html);
    }

    public function testVerifyReturnsFalseIfInputIsEmpty()
    {
        $captcha = new CloudflareTurnstile('site-key', 'secret-key');
        $this->assertFalse($captcha->verify(''));
    }

    public function testVerifyReturnsFalseIfApiFails()
    {
        $captcha = $this->getMockBuilder(CloudflareTurnstile::class)
            ->setConstructorArgs(['site-key', 'secret-key'])
            ->onlyMethods(['fetchVerifyResponse'])
            ->getMock();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => false]));
        $this->assertFalse($captcha->verify('dummy-token'));
    }

    public function testVerifyReturnsTrueIfApiSucceeds()
    {
        $captcha = $this->getMockBuilder(CloudflareTurnstile::class)
            ->setConstructorArgs(['site-key', 'secret-key'])
            ->onlyMethods(['fetchVerifyResponse'])
            ->getMock();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => true]));
        $this->assertTrue($captcha->verify('dummy-token'));
    }
}
