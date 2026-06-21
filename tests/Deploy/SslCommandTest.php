<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\SslCommand;
use PHPUnit\Framework\TestCase;

final class SslCommandTest extends TestCase
{
    public function testBuildCertbotScriptWithEmail(): void
    {
        $command = new SslCommand();
        $method = new \ReflectionMethod($command, 'buildCertbotScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', 'admin@example.com', 'production');

        $this->assertStringContainsString('set -e', $script);
        $this->assertStringContainsString('/etc/nginx/sites-available/example.com.conf', $script);
        $this->assertStringContainsString('-d example.com', $script);
        $this->assertStringContainsString('--email admin@example.com', $script);
        $this->assertStringContainsString('--non-interactive', $script);
        $this->assertStringContainsString('--agree-tos', $script);
        $this->assertStringContainsString('--redirect', $script);
        $this->assertStringContainsString('--hsts', $script);
        $this->assertStringContainsString('--staple-ocsp', $script);
    }

    public function testBuildCertbotScriptWithoutEmail(): void
    {
        $command = new SslCommand();
        $method = new \ReflectionMethod($command, 'buildCertbotScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', null, 'production');

        $this->assertStringContainsString('--register-unsafely-without-email', $script);
        $this->assertStringNotContainsString('--email', $script);
    }

    public function testBuildCertbotScriptChecksNginxConfigFirst(): void
    {
        $command = new SslCommand();
        $method = new \ReflectionMethod($command, 'buildCertbotScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', 'a@b.com', 'production');

        $this->assertStringContainsString('if [ ! -f "/etc/nginx/sites-available/example.com.conf" ]', $script);
        $this->assertStringContainsString('exit 1', $script);
    }

    public function testBuildCertbotScriptContainsCorrectEnvHint(): void
    {
        $command = new SslCommand();
        $method = new \ReflectionMethod($command, 'buildCertbotScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', 'a@b.com', 'staging');

        $this->assertStringContainsString('php console server:site:add staging', $script);
    }
}
