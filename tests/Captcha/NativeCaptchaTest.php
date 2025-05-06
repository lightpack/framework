<?php

namespace Lightpack\Captcha;

use Lightpack\Container\Container;
use Lightpack\Session\Drivers\ArrayDriver;
use PHPUnit\Framework\TestCase;

class NativeCaptchaTest extends TestCase
{
    private string $font;
    private Container $container;
    private ArrayDriver $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->font = __DIR__ . '/FreeSans.ttf';
        if (!file_exists($this->font)) {
            $this->markTestSkipped('Test font not found: ' . $this->font);
        }
        $this->container = Container::getInstance();
        $this->container->register('session', function() {
            return new ArrayDriver;
        });
    }

    private function getCaptchaInstance(): NativeCaptcha
    {
        return new NativeCaptcha(
            $this->container->get('session')
        );
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
