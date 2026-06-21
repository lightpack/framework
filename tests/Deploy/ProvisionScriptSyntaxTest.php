<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProvisionScriptSyntaxTest extends TestCase
{
    private string $scriptPath;

    public function setUp(): void
    {
        $this->scriptPath = __DIR__ . '/../../src/Framework/Deploy/scripts/provision.sh';
    }

    public function testScriptFileExists(): void
    {
        $this->assertFileExists($this->scriptPath);
    }

    public function testScriptBashSyntaxIsValid(): void
    {
        $output = [];
        $exitCode = 0;

        exec('bash -n ' . escapeshellarg($this->scriptPath) . ' 2>&1', $output, $exitCode);

        $this->assertEquals(0, $exitCode, 'provision.sh has bash syntax errors: ' . implode("\n", $output));
    }

    public function testScriptStartsWithShebang(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertStringStartsWith("#!/bin/bash\n", $content);
    }

    public function testScriptContainsPhpVersionValidation(): void
    {
        $content = file_get_contents($this->scriptPath);

        // Should validate PHP versions
        $this->assertStringContainsString('8.2', $content);
        $this->assertStringContainsString('8.3', $content);
        $this->assertStringContainsString('8.4', $content);
    }

    public function testScriptContainsUbuntuVersionChecks(): void
    {
        $content = file_get_contents($this->scriptPath);

        // Should reference supported Ubuntu codenames
        $this->assertStringContainsString('jammy', $content);
        $this->assertStringContainsString('noble', $content);
    }

    public function testScriptHasErrorHandlingWithSetE(): void
    {
        $content = file_get_contents($this->scriptPath);

        // The script should have set -e or similar error handling
        // Note: the main script may not have set -e at the very top
        // because it does error handling via functions, but let's check
        // for common patterns
        $this->assertStringContainsString('set -e', $content);
    }

    public function testScriptCreatesDeployUser(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('DEPLOY_USER', $content);
        $this->assertStringContainsString('useradd', $content);
    }

    public function testScriptInstallsNginx(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('nginx', $content);
    }

    public function testScriptInstallsPhpFpm(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('php', $content);
        $this->assertStringContainsString('fpm', $content);
    }

    public function testScriptConfiguresMysql(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('mysql', $content);
    }

    public function testScriptConfiguresFirewall(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('ufw', $content);
    }

    public function testScriptSetsUpSudoers(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('sudoers', $content);
    }

    public function testScriptInstallsComposer(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('composer', $content);
    }

    public function testScriptInstallsSupervisor(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('supervisor', $content);
    }

    public function testScriptConfiguresFail2ban(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('fail2ban', $content);
    }

    public function testScriptConfiguresSshHardening(): void
    {
        $content = file_get_contents($this->scriptPath);

        $this->assertStringContainsString('sshd_config', $content);
    }
}
