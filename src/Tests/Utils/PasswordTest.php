<?php

declare(strict_types=1);

use Lightpack\Utils\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testPasswordCanVerifyHash()
    {
        $password = new Password();
        $verified = $password->verify('lightpack', $password->hash('lightpack'));

        $this->assertTrue($verified);
    }

    public function testGenerateRandomPassword()
    {
        $password = new Password();
        $pass1 = $password->generate();
        $pass2 = $password->generate(16);

        $this->assertEquals(8, strlen($pass1));
        $this->assertEquals(16, strlen($pass2));

        // expect exception
        $this->expectException(\Exception::class);
        $password->generate(5);
    }

    public function testPasswordStrength()
    {
        $password = new Password();

        $this->assertEquals('weak', $password->strength('123456'));
        $this->assertEquals('weak', $password->strength('12345678'));
        $this->assertEquals('weak', $password->strength('abcdefgh'));
        $this->assertEquals('weak', $password->strength('ABCDEFGH'));
        $this->assertEquals('medium', $password->strength('abc123ABC'));
        $this->assertEquals('medium', $password->strength('abcdefgh123456'));
        $this->assertEquals('medium', $password->strength('ABCDEFGHabcdefgh123456'));
        $this->assertEquals('strong', $password->strength('A123#abc'));
        $this->assertEquals('strong', $password->strength('%1BCD23c'));
        $this->assertEquals('strong', $password->strength('12345678%1BCD23c'));
    }
}
