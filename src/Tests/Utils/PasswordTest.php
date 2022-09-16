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
}
