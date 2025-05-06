<?php

namespace Lightpack\Captcha;

use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

class GoogleReCaptchaTest extends TestCase
{
    protected Request $request;

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

    private function getCaptchaInstance(): GoogleReCaptcha
    {
        return new GoogleReCaptcha($this->request, 'site-key', 'secret-key');
    }

    private function getMockCaptchaInstance()
    {
        return $this->getMockBuilder(GoogleReCaptcha::class)
        ->setConstructorArgs([$this->request, 'site-key', 'secret-key'])
        ->onlyMethods(['fetchVerifyResponse'])
        ->getMock();
    }

    public function testGenerateReturnsHtmlWithSiteKey()
    {
        $captcha = $this->getCaptchaInstance();
        $html = $captcha->generate();
        $this->assertStringContainsString('g-recaptcha', $html);
        $this->assertStringContainsString('site-key', $html);
    }

    public function testVerifyReturnsFalseIfInputIsEmpty()
    {
        $_POST['g-recaptcha-response'] = '';
        $captcha = $this->getCaptchaInstance();
        $this->assertFalse($captcha->verify());
    }

    public function testVerifyReturnsFalseIfApiFails()
    {
        $_POST['g-recaptcha-response'] = 'dummy-token';
        $captcha = $this->getMockCaptchaInstance();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => false]));
        $this->assertFalse($captcha->verify());
    }

    public function testVerifyReturnsTrueIfApiSucceeds()
    {
        $_POST['g-recaptcha-response'] = 'dummy-token';
        $captcha = $this->getMockCaptchaInstance();
        $captcha->method('fetchVerifyResponse')->willReturn(json_encode(['success' => true]));
        $this->assertTrue($captcha->verify());
    }
}
