<?php

namespace Lightpack\Tests\Mfa;

use PHPUnit\Framework\TestCase;
use Lightpack\Mfa\Factor\BackupCodeMfa;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Mfa\BackupCodeHelper;

class BackupCodeMfaTest extends TestCase
{
    protected $user;
    protected $factor;

    protected function setUp(): void
    {
        $this->factor = new BackupCodeMfa();
        $this->user = $this->getMockBuilder(AuthUser::class)
            ->onlyMethods(['save'])
            ->getMock();
    }

    public function testValidateReturnsFalseIfNoInput()
    {
        $this->user->mfa_backup_codes = ['code1', 'code2'];
        $this->assertFalse($this->factor->validate($this->user, null));
        $this->assertFalse($this->factor->validate($this->user, ''));
    }

    public function testValidateReturnsFalseIfNoCodes()
    {
        $this->user->mfa_backup_codes = [];
        $this->assertFalse($this->factor->validate($this->user, 'code1'));
    }

    public function testValidateReturnsFalseIfCodeIsInvalid()
    {
        $this->user->mfa_backup_codes = ['code1', 'code2'];
        $this->assertFalse($this->factor->validate($this->user, 'invalid'));
    }

    public function testValidateRemovesCodeAndReturnsTrue()
    {
        $codes = ['abc123', 'def456', 'xyz789'];
        $hashCodes = BackupCodeHelper::hashCodes($codes);
        $this->user->mfa_backup_codes = $hashCodes;
        $result = $this->factor->validate($this->user, 'def456');
        $this->assertTrue($result);
        $remaining = $this->user->mfa_backup_codes;
        $this->assertNotContains('def456', $remaining);
        $this->assertCount(2, $remaining);
    }

    public function testGetName()
    {
        $this->assertSame('backup_code', $this->factor->getName());
    }
}
