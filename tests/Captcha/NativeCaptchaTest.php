<?php

namespace Lightpack\Captcha;

use Lightpack\Config\Config;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Session;
use PHPUnit\Framework\TestCase;

class NativeCaptchaTest extends TestCase
{
    private string $font;
    private Config $config;
    private $driver;
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
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
    }

    private function getCaptchaInstance(): NativeCaptcha
    {
        return new NativeCaptcha($this->session);
    }

    public function testCaptchaGenerationStoresTextInSession()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
        $this->assertNotEmpty($this->session->get('_captcha'));
    }

    public function testCustomTextIsStoredAndRendered()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('TEST12')
            ->generate();
        $this->assertEquals('TEST12', $this->session->get('_captcha'));
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
        $this->getCaptchaInstance()->generate();
    }

    public function testVerifyReturnsTrueForCorrectInput()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('ABC123');
        $captcha->generate();
        $this->assertTrue($captcha->verify('ABC123'));
    }

    public function testVerifyReturnsFalseForIncorrectInput()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('ABC123');
        $captcha->generate();
        $this->assertFalse($captcha->verify('WRONG'));
    }

    public function testVerifyIsCaseSensitive()
    {
        $captcha = $this->getCaptchaInstance()
            ->font($this->font)
            ->text('AbC123');
        $captcha->generate();
        $this->assertFalse($captcha->verify('abc123'));
    }

    public function testVerifyFailsIfNoCaptchaInSession()
    {
        $captcha = $this->getCaptchaInstance();
        $this->assertFalse($captcha->verify('ANY'));
    }
}
