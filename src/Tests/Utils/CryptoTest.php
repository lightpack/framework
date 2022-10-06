<?php

namespace Tests\Utils;

use Lightpack\Utils\Crypto;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
    public function testItCanEncryptAndDecrypt()
    {
        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('Hello World', 'secret');
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted, 'secret'));
    }

    public function testItGeneratesDifferentEncryptedValues()
    {
        $crypto = new Crypto();
        $encrypted1 = $crypto->encrypt('Hello World', 'secret');
        $encrypted2 = $crypto->encrypt('Hello World', 'secret');
        
        $this->assertNotEquals($encrypted1, $encrypted2);
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted1, 'secret'));
        $this->assertEquals('Hello World', $crypto->decrypt($encrypted2, 'secret'));
    }
}