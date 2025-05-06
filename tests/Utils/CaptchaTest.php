<?php

namespace Lightpack\Utils;

use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    private string $font;

    protected function setUp(): void
    {
        parent::setUp();
        // Use the test font from fixtures
        $this->font = __DIR__ . '/fixtures/FreeSans.ttf';
        if (!file_exists($this->font)) {
            $this->markTestSkipped('Test font not found: ' . $this->font);
        }
    }

    public function testBasicCaptchaGeneration()
    {
        $captcha = (new Captcha())
            ->font($this->font)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testCustomText()
    {
        $captcha = (new Captcha())
            ->font($this->font)
            ->text('ABC123')
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testCustomSize()
    {
        $captcha = (new Captcha())
            ->font($this->font)
            ->width(200)
            ->height(80)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testThrowsIfFontMissing()
    {
        $this->expectException(\Exception::class);
        (new Captcha())->generate();
    }

    public function testGeneratedCaptchaIsRandomByDefault()
    {
        $captcha1 = (new Captcha())
            ->font($this->font)
            ->generate();
        $captcha2 = (new Captcha())
            ->font($this->font)
            ->generate();
        $this->assertNotEquals($captcha1, $captcha2, 'Generated captchas should be random');
    }

    public function testCustomTextLength()
    {
        $text = str_repeat('A', 10);
        $captcha = (new Captcha())
            ->font($this->font)
            ->text($text)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captcha);
    }

    public function testMinAndMaxImageSize()
    {
        $captchaMin = (new Captcha())
            ->font($this->font)
            ->width(50)
            ->height(20)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captchaMin);
        $captchaMax = (new Captcha())
            ->font($this->font)
            ->width(500)
            ->height(200)
            ->generate();
        $this->assertStringStartsWith('data:image/png;base64,', $captchaMax);
    }

    public function testThrowsIfGdMissing()
    {
        if (extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is loaded, cannot test missing GD.');
        }
        $captcha = (new Captcha())
            ->font($this->font);
        $this->expectException(\Exception::class);
        $captcha->generate();
    }

    public function testImageOutputIsValidPng()
    {
        $captcha = (new Captcha())
            ->font($this->font)
            ->generate();
        $data = base64_decode(substr($captcha, strlen('data:image/png;base64,')));
        $image = @imagecreatefromstring($data);
        $this->assertNotFalse($image, 'Captcha output should be valid PNG image data');
        if ($image !== false) {
            imagedestroy($image);
        }
    }
}
