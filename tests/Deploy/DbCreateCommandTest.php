<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\DbCreateCommand;
use PHPUnit\Framework\TestCase;

final class DbCreateCommandTest extends TestCase
{
    public function testBuildCreateScriptCallsWrapper(): void
    {
        $command = new DbCreateCommand();
        $method = new \ReflectionMethod($command, 'buildCreateScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'myapp', 'myuser', 'secret123');

        $this->assertStringContainsString('set -e', $script);
        $this->assertStringContainsString('lp-mysql-create', $script);
        $this->assertStringContainsString("'myapp'", $script);
        $this->assertStringContainsString("'myuser'", $script);
        $this->assertStringContainsString("'secret123'", $script);
    }

    public function testBuildCreateScriptEscapesShellArguments(): void
    {
        $command = new DbCreateCommand();
        $method = new \ReflectionMethod($command, 'buildCreateScript');
        $method->setAccessible(true);

        // Password with special chars that need escaping
        $script = $method->invoke($command, 'myapp', 'myuser', "pass'word");

        $this->assertStringContainsString("'pass'\\''word'", $script);
    }

    public function testBuildCreateScriptOutputsConfirmation(): void
    {
        $command = new DbCreateCommand();
        $method = new \ReflectionMethod($command, 'buildCreateScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, 'shopdb', 'shopuser', 'secret');

        $this->assertStringContainsString('Database [shopdb] created', $script);
    }
}
