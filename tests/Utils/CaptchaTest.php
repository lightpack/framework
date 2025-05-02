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
}
