<?php

declare(strict_types=1);

use Lightpack\Deploy\Deployer;
use PHPUnit\Framework\TestCase;

final class DeployerTest extends TestCase
{
    private array $config;

    public function setUp(): void
    {
        $this->config = [
            'production' => [
                'host' => '1.2.3.4',
                'path' => '/var/www/app',
                'repo' => 'git@github.com:user/repo.git',
                'branch' => 'main',
                'key' => '~/.ssh/id_rsa',
            ],
            'staging' => [
                'host' => '5.6.7.8',
                'path' => '/var/www/staging',
                'key' => '/home/user/.ssh/staging',
            ],
        ];
    }

    public function testGetEnvironments(): void
    {
        $deployer = new Deployer($this->config);
        $this->assertEquals(['production', 'staging'], $deployer->getEnvironments());
    }

    public function testDeployThrowsOnMissingEnvironment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Environment 'missing' not found in config/deploy.php");

        $deployer = new Deployer($this->config);
        $deployer->deploy('missing');
    }

    public function testRollbackThrowsOnMissingEnvironment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Environment 'missing' not found in config/deploy.php");

        $deployer = new Deployer($this->config);
        $deployer->rollback('missing');
    }

    public function testSyncEnvThrowsOnMissingEnvironment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Environment 'missing' not found in config/deploy.php");

        $deployer = new Deployer($this->config);
        $deployer->syncEnv('missing', '/tmp/.env');
    }

    // ─────────────────────────────────────────────
    // buildCodeScript (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildCodeScriptWithRepo(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildCodeScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['production']);

        $this->assertStringContainsString('test -d /var/www/app/.git || git -C', $script);
        $this->assertStringContainsString("git -C '/var/www/app' remote set-url origin", $script);
        $this->assertStringContainsString("git -C '/var/www/app' fetch origin 'main'", $script);
        $this->assertStringContainsString("git -C '/var/www/app' reset --hard", $script);
        $this->assertStringContainsString("composer -d '/var/www/app' install --no-dev --optimize-autoloader", $script);
    }

    public function testBuildCodeScriptWithoutRepo(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildCodeScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['staging']);

        // Should NOT contain repo setup steps
        $this->assertStringNotContainsString('remote set-url origin', $script);
        $this->assertStringContainsString("git -C '/var/www/staging' fetch origin 'main'", $script);
        $this->assertStringContainsString("composer -d '/var/www/staging' install --no-dev --optimize-autoloader", $script);
    }

    public function testBuildCodeScriptEscapesShellArgs(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildCodeScript');
        $method->setAccessible(true);

        $env = [
            'path' => '/var/www/my app', // space in path
            'branch' => 'feature/test',
            'repo' => null,
        ];

        $script = $method->invoke($deployer, $env);

        // The path should be shell-escaped in the script
        $this->assertStringContainsString("'/var/www/my app'", $script);
        $this->assertStringContainsString("'feature/test'", $script);
    }

    // ─────────────────────────────────────────────
    // buildActivateScript (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildActivateScriptContainsStoragePermissions(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildActivateScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['production']);

        $this->assertStringContainsString("find '/var/www/app/storage' -type d -exec chmod 2775", $script);
        $this->assertStringContainsString("chgrp www-data", $script);
        $this->assertStringContainsString("php '/var/www/app/console' migrate:up --force", $script);
    }

    public function testBuildActivateScriptContainsPhpFpmReload(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildActivateScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['production']);

        $this->assertStringContainsString('php -r', $script);
        $this->assertStringContainsString('systemctl reload php', $script);
    }

    // ─────────────────────────────────────────────
    // buildRollbackScript (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildRollbackScriptStructure(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildRollbackScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['production'], 3);

        $this->assertStringContainsString("cd '/var/www/app'", $script);
        $this->assertStringContainsString('git log --oneline -5', $script);
        $this->assertStringContainsString('git reset --hard HEAD~3', $script);
        $this->assertStringContainsString("composer -d '/var/www/app' install --no-dev --optimize-autoloader", $script);
        $this->assertStringContainsString('Rolled back. Current commit:', $script);
        $this->assertStringContainsString('systemctl reload php', $script);
    }

    public function testBuildRollbackScriptWithSingleStep(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildRollbackScript');
        $method->setAccessible(true);

        $script = $method->invoke($deployer, $this->config['production'], 1);

        $this->assertStringContainsString('git reset --hard HEAD~1', $script);
    }

    // ─────────────────────────────────────────────
    // buildSshCommand (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildSshCommandStructure(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildSshCommand');
        $method->setAccessible(true);

        $cmd = $method->invoke($deployer, $this->config['production'], 'echo hello');

        $this->assertEquals('ssh', $cmd[0]);
        $this->assertEquals('-n', $cmd[1]);
        $this->assertEquals('-i', $cmd[2]);
        $this->assertEquals('deploy@1.2.3.4', $cmd[8]);
        $this->assertEquals('echo hello', $cmd[9]);
    }

    // ─────────────────────────────────────────────
    // buildScpCommand (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildScpCommandStructure(): void
    {
        $deployer = new Deployer($this->config);
        $method = new \ReflectionMethod($deployer, 'buildScpCommand');
        $method->setAccessible(true);

        $cmd = $method->invoke($deployer, $this->config['production'], '/local/.env.production');

        $this->assertEquals('scp', $cmd[0]);
        $this->assertEquals('-i', $cmd[1]);
        $this->assertEquals('/local/.env.production', $cmd[5]);
        $this->assertEquals('deploy@1.2.3.4:/var/www/app/.env', $cmd[6]);
    }
}
