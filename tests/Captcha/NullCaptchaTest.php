<?php

namespace Lightpack\Captcha;

use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

class NullCaptchaTest extends TestCase
{
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
    }

    public function testGenerateReturnsEmptyString()
    {
        $captcha = new NullCaptcha($this->request);
        $this->assertSame('', $captcha->generate());
    }

    public function testVerifyAlwaysReturnsTrue()
    {
        $captcha = new NullCaptcha($this->request);
        $this->assertTrue($captcha->verify());
    }
}
