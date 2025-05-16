<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Utils\Otp;

class OtpTest extends TestCase
{
    public function testGeneratesNumericCodeOfGivenLength()
    {
        $otp = (new Otp)->length(4)->type('numeric');
        $code = $otp->generate();
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $code);
    }

    public function testGeneratesAlphaCodeOfGivenLength()
    {
        $otp = (new Otp)->length(6)->type('alpha');
        $code = $otp->generate();
        $this->assertMatchesRegularExpression('/^[A-Z]{6}$/', $code);
    }

    public function testGeneratesAlnumCodeOfGivenLength()
    {
        $otp = (new Otp)->length(8)->type('alnum');
        $code = $otp->generate();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
    }

    public function testGeneratesCodeWithCustomCharset()
    {
        $otp = (new Otp)->length(5)->type('custom')->charset('ABC123');
        $code = $otp->generate();
        $this->assertMatchesRegularExpression('/^[ABC123]{5}$/', $code);
    }

    public function testInvalidLengthDefaultsToSix()
    {
        $otp = (new Otp)->length(0)->type('numeric');
        $code = $otp->generate();
        $this->assertEquals(6, strlen($code));
    }
}
