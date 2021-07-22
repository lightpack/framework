<?php

declare(strict_types=1);

use Lightpack\Crypto\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testPasswordCanVerifyHash()
    {
        $verified = Password::verify('lightpack', Password::hash('lightpack'));

        $this->assertEquals(true, $verified);
    }
}
