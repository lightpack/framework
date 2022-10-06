<?php

namespace Tests\Utils;

use Lightpack\Utils\Crypto;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
    public function testItCanEncryptAndDecrypt()
    {
        $crypto = new Crypto('secret');
        $encrypted = $crypto->encrypt('Hello World');
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted));
    }

    public function testItGeneratesDifferentEncryptedValues()
    {
        $crypto = new Crypto('secret');
        $encrypted1 = $crypto->encrypt('Hello World');
        $encrypted2 = $crypto->encrypt('Hello World');
        
        $this->assertNotEquals($encrypted1, $encrypted2);
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted1));
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted2));
    }
}