<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\SiteRemoveCommand;
use PHPUnit\Framework\TestCase;

final class SiteRemoveCommandTest extends TestCase
{
    public function testBuildRemoveScriptDisablesAndRemovesSite(): void
    {
        $command = new SiteRemoveCommand;
        $method = new \ReflectionMethod($command, 'buildRemoveScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com');

        $this->assertStringContainsString('lp-nginx-disable', $script);
        $this->assertStringContainsString('${domain}.conf', $script);
        $this->assertStringContainsString('certbot delete --cert-name "example.com"', $script);
        $this->assertStringContainsString('systemctl reload nginx', $script);
        $this->assertStringContainsString('Site ${domain} removed', $script);
    }

    public function testBuildRemoveScriptIgnoresCertbotErrors(): void
    {
        $command = new SiteRemoveCommand;
        $method = new \ReflectionMethod($command, 'buildRemoveScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'example.com');

        $this->assertStringContainsString('2>/dev/null || true', $script);
    }
}
