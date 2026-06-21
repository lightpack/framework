<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\SiteAddCommand;
use PHPUnit\Framework\TestCase;

final class SiteAddCommandTest extends TestCase
{
    public function testBuildSiteScriptContainsNginxConfig(): void
    {
        $command = new SiteAddCommand();
        $method = new \ReflectionMethod($command, 'buildSiteScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', '/var/www/app');

        $this->assertStringContainsString('set -e', $script);
        $this->assertStringContainsString('server_name example.com', $script);
        $this->assertStringContainsString('root /var/www/app/public', $script);
        $this->assertStringContainsString('index index.php', $script);
        $this->assertStringContainsString('try_files $uri $uri/ /index.php?$query_string', $script);
        $this->assertStringContainsString('fastcgi_pass unix:PHP_FPM_SOCKET', $script);
        $this->assertStringContainsString('X-Frame-Options "SAMEORIGIN"', $script);
        $this->assertStringContainsString('X-Content-Type-Options "nosniff"', $script);
        $this->assertStringContainsString('gzip on', $script);
    }

    public function testBuildSiteScriptDetectsPhpFpmSocket(): void
    {
        $command = new SiteAddCommand();
        $method = new \ReflectionMethod($command, 'buildSiteScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', '/var/www/app');

        $this->assertStringContainsString('ls /run/php/php*-fpm.sock', $script);
        $this->assertStringContainsString('PHP_FPM_SOCK', $script);
    }

    public function testBuildSiteScriptUsesWrapperCommands(): void
    {
        $command = new SiteAddCommand();
        $method = new \ReflectionMethod($command, 'buildSiteScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', '/var/www/app');

        $this->assertStringContainsString('lp-nginx-write', $script);
        $this->assertStringContainsString('lp-nginx-enable', $script);
        $this->assertStringContainsString('systemctl reload nginx', $script);
    }

    public function testBuildSiteScriptEscapesDomainInBash(): void
    {
        $command = new SiteAddCommand();
        $method = new \ReflectionMethod($command, 'buildSiteScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com', '/var/www/app');

        // The domain is interpolated directly in the heredoc, so it should appear raw
        $this->assertStringContainsString('example.com', $script);
        // The wrapper uses the domain as a filename
        $this->assertStringContainsString('"example.com.conf"', $script);
    }
}
