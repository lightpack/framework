<?php

namespace Lightpack\Captcha;

use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

class GoogleReCaptchaInvisibleTest extends TestCase
{
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->request = new Request();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    private function getCaptchaInstance(): GoogleReCaptchaInvisible
    {
        return new GoogleReCaptchaInvisible($this->request, 'site-key', 'secret-key');
    }

    private function getMockCaptchaInstance()
    {
        return $this->getMockBuilder(GoogleReCaptchaInvisible::class)
            ->setConstructorArgs([$this->request, 'site-key', 'secret-key'])
            ->onlyMethods(['fetchVerifyResponse'])
            ->getMock();
    }

    public function testGenerateReturnsInvisibleButtonHtml()
    {
        $captcha = $this->getCaptchaInstance();
        $html = $captcha->generate();
        $this->assertStringContainsString('g-recaptcha', $html);
        $this->assertStringContainsString('data-size="invisible"', $html);
        $this->assertStringContainsString('site-key', $html);
    }

    public function testVerifyReturnsFalseIfInputIsEmpty()
    {
        $captcha = $this->getCaptchaInstance();
        $this->assertFalse($captcha->verify(''));
    }

    public function testVerifyReturnsFalseIfApiFails()
    {
        $captcha = $this->getMockCaptchaInstance();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => false]));
        $_POST['g-recaptcha-response'] = 'dummy-token';
        $this->assertFalse($captcha->verify());
    }

    public function testVerifyReturnsTrueIfApiSucceeds()
    {
        $captcha = $this->getMockCaptchaInstance();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => true]));
        $_POST['g-recaptcha-response'] = 'dummy-token';
        $this->assertTrue($captcha->verify());
    }
}
