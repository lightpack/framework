<?php

namespace Lightpack\Captcha;

use Lightpack\Config\Config;
use Lightpack\Http\Request;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Session;
use PHPUnit\Framework\TestCase;

class NativeCaptchaTest extends TestCase
{
    private string $font;
    private Config $config;
    private $driver;
    private Session $session;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Ensure DIR_CONFIG is defined for Config loading
        if (!defined('DIR_CONFIG')) {
            define('DIR_CONFIG', __DIR__ . '/tmp');
        }
        $this->font = __DIR__ . '/FreeSans.ttf';
        if (!file_exists($this->font)) {
            $this->markTestSkipped('Test font not found: ' . $this->font);
        }
        $this->config = new Config();
        $this->driver = new ArrayDriver();
        $this->session = new Session($this->driver, $this->config);
        $this->request = new Request();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    private function getCaptchaInstance(): NativeCaptcha
    {
        return new NativeCaptcha($this->request, $this->session);
    }

    public function testCaptchaGenerationStoresTextInSession()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
        $this->assertNotEmpty($this->session->get('_captcha_text'));
    }

    public function testCustomTextIsStoredAndRendered()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('TEST12')
            ->generate();
        $this->assertEquals('TEST12', $this->session->get('_captcha_text'));
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testCustomSize()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->width(180)
            ->height(70)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testThrowsIfFontMissing()
    {
        $this->expectException(\Exception::class);
        (new NativeCaptcha($this->request, $this->session))->generate();
    }

    public function testVerifyReturnsTrueForCorrectInput()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('ABC123');
        $captcha->generate();
        $_POST['captcha'] = 'ABC123';
        $this->assertTrue($captcha->verify());
    }

    public function testVerifyReturnsFalseForIncorrectInput()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('ABC123');
        $captcha->generate();
        $_POST['captcha'] = 'WRONG';
        $this->assertFalse($captcha->verify());
    }

    public function testVerifyIsCaseSensitive()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('AbC123');
        $captcha->generate();
        $_POST['captcha'] = 'abc123';
        $this->assertFalse($captcha->verify());
    }

    public function testVerifyFailsIfNoCaptchaInSession()
    {
        $captcha = $this->getCaptchaInstance();
        $_POST['captcha'] = 'ANY';
        $this->assertFalse($captcha->verify());
    }
}
