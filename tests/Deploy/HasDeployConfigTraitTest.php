<?php

declare(strict_types=1);

use Lightpack\Deploy\HasDeployConfigTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test double that exposes private trait methods for unit testing.
 */
final class HasDeployConfigTraitTestDouble
{
    use HasDeployConfigTrait;

    public function testResolveKeyPath(string $key): string
    {
        return $this->resolveKeyPath($key);
    }

    public function testValidateDomain(string $domain): bool
    {
        return $this->validateDomain($domain);
    }

    public function testFormatBytes(int $bytes): string
    {
        return $this->formatBytes($bytes);
    }

    public function testBuildSshCommand(array $envConfig, string $remoteScript): array
    {
        return $this->buildSshCommand($envConfig, $remoteScript);
    }

    public function testGetEnvConfig(array $config, string $env): ?array
    {
        return $this->getEnvConfig($config, $env);
    }
}

final class HasDeployConfigTraitTest extends TestCase
{
    private HasDeployConfigTraitTestDouble $helper;

    public function setUp(): void
    {
        $this->helper = new HasDeployConfigTraitTestDouble();
    }

    // ─────────────────────────────────────────────
    // resolveKeyPath
    // ─────────────────────────────────────────────

    public function testResolveKeyPathExpandsTilde(): void
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
        $this->assertEquals("{$home}/.ssh/id_rsa", $this->helper->testResolveKeyPath('~/.ssh/id_rsa'));
    }

    public function testResolveKeyPathLeavesAbsoluteUntouched(): void
    {
        $this->assertEquals('/home/user/.ssh/id_rsa', $this->helper->testResolveKeyPath('/home/user/.ssh/id_rsa'));
    }

    public function testResolveKeyPathLeavesRelativeUntouched(): void
    {
        $this->assertEquals('.ssh/id_rsa', $this->helper->testResolveKeyPath('.ssh/id_rsa'));
    }

    // ─────────────────────────────────────────────
    // validateDomain
    // ─────────────────────────────────────────────

    public function testValidateDomainAcceptsValidDomain(): void
    {
        $this->assertTrue($this->helper->testValidateDomain('example.com'));
        $this->assertTrue($this->helper->testValidateDomain('sub.example.com'));
        $this->assertTrue($this->helper->testValidateDomain('api.example.co.uk'));
    }

    public function testValidateDomainAcceptsIpAddress(): void
    {
        $this->assertTrue($this->helper->testValidateDomain('192.168.1.1'));
        $this->assertTrue($this->helper->testValidateDomain('10.0.0.1'));
    }

    public function testValidateDomainRejectsPathTraversal(): void
    {
        $this->assertFalse($this->helper->testValidateDomain('example.com/../etc'));
        $this->assertFalse($this->helper->testValidateDomain('../etc/passwd'));
    }

    public function testValidateDomainRejectsSpecialChars(): void
    {
        $this->assertFalse($this->helper->testValidateDomain('example.com; rm -rf /'));
        $this->assertFalse($this->helper->testValidateDomain('example.com|cat'));
        $this->assertFalse($this->helper->testValidateDomain('example.com$(whoami)'));
        $this->assertFalse($this->helper->testValidateDomain('example.com`id`'));
    }

    public function testValidateDomainRejectsEmpty(): void
    {
        $this->assertFalse($this->helper->testValidateDomain(''));
    }

    public function testValidateDomainRejectsSingleLabel(): void
    {
        $this->assertFalse($this->helper->testValidateDomain('localhost'));
    }

    // ─────────────────────────────────────────────
    // formatBytes
    // ─────────────────────────────────────────────

    public function testFormatBytesBytes(): void
    {
        $this->assertEquals('512 bytes', $this->helper->testFormatBytes(512));
    }

    public function testFormatBytesKilobytes(): void
    {
        $this->assertEquals('1.50 KB', $this->helper->testFormatBytes(1536));
    }

    public function testFormatBytesMegabytes(): void
    {
        $this->assertEquals('2.50 MB', $this->helper->testFormatBytes(2621440));
    }

    public function testFormatBytesGigabytes(): void
    {
        $this->assertEquals('1.00 GB', $this->helper->testFormatBytes(1073741824));
    }

    public function testFormatBytesZero(): void
    {
        $this->assertEquals('0 bytes', $this->helper->testFormatBytes(0));
    }

    // ─────────────────────────────────────────────
    // buildSshCommand
    // ─────────────────────────────────────────────

    public function testBuildSshCommandStructure(): void
    {
        $envConfig = ['host' => '1.2.3.4', 'key' => '/home/user/.ssh/id_rsa'];
        $cmd = $this->helper->testBuildSshCommand($envConfig, 'echo hello');

        $this->assertEquals('ssh', $cmd[0]);
        $this->assertEquals('-n', $cmd[1]);
        $this->assertEquals('-i', $cmd[2]);
        $this->assertEquals('/home/user/.ssh/id_rsa', $cmd[3]);
        $this->assertEquals('-o', $cmd[4]);
        $this->assertEquals('StrictHostKeyChecking=accept-new', $cmd[5]);
        $this->assertEquals('-o', $cmd[6]);
        $this->assertEquals('ConnectTimeout=10', $cmd[7]);
        $this->assertEquals('deploy@1.2.3.4', $cmd[8]);
        $this->assertEquals('echo hello', $cmd[9]);
    }

    public function testBuildSshCommandExpandsTildeInKey(): void
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
        $envConfig = ['host' => '5.6.7.8', 'key' => '~/.ssh/deploy'];
        $cmd = $this->helper->testBuildSshCommand($envConfig, 'ls');

        $this->assertEquals("{$home}/.ssh/deploy", $cmd[3]);
    }

    // ─────────────────────────────────────────────
    // getEnvConfig
    // ─────────────────────────────────────────────

    public function testGetEnvConfigReturnsExisting(): void
    {
        $config = ['production' => ['host' => '1.2.3.4'], 'staging' => ['host' => '5.6.7.8']];
        $this->assertEquals(['host' => '1.2.3.4'], $this->helper->testGetEnvConfig($config, 'production'));
    }

    public function testGetEnvConfigReturnsNullForMissing(): void
    {
        $config = ['production' => ['host' => '1.2.3.4']];
        $this->assertNull($this->helper->testGetEnvConfig($config, 'staging'));
    }
}
