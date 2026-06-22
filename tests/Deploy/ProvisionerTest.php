<?php

declare(strict_types=1);

use Lightpack\Deploy\Provisioner;
use PHPUnit\Framework\TestCase;

final class ProvisionerTest extends TestCase
{
    private array $config;

    public function setUp(): void
    {
        $this->config = [
            'production' => [
                'host' => '1.2.3.4',
                'key' => '~/.ssh/id_rsa',
                'path' => '/var/www/app',
                'repo' => 'git@github.com:user/repo.git',
            ],
            'staging' => [
                'host' => '5.6.7.8',
                'key' => '/home/user/.ssh/staging',
                'path' => '/var/www/staging',
            ],
        ];
    }

    public function testGetEnvironments(): void
    {
        $provisioner = new Provisioner($this->config);
        $this->assertEquals(['production', 'staging'], $provisioner->getEnvironments());
    }

    public function testProvisionThrowsOnMissingEnvironment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Environment 'missing' not found in config/deploy.php");

        $provisioner = new Provisioner($this->config);
        $provisioner->provision('missing', []);
    }

    // ─────────────────────────────────────────────
    // buildProvisionOptions (via reflection)
    // ─────────────────────────────────────────────

    public function testBuildProvisionOptionsDefaults(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'buildProvisionOptions');
        $method->setAccessible(true);

        $params = [
            'name' => 'production',
            'php_version' => '8.3',
            'db_name' => 'myapp',
            'db_user' => 'myapp',
            'timezone' => 'UTC',
        ];

        $options = $method->invoke($provisioner, $this->config['production'], $params);

        $this->assertEquals('production', $options['SERVER_NAME']);
        $this->assertEquals('deploy', $options['DEPLOY_USER']);
        $this->assertEquals('8.3', $options['PHP_VERSION']);
        $this->assertEquals('UTC', $options['TIMEZONE']);
        $this->assertEquals('mysql', $options['DB_TYPE']);
        $this->assertEquals('nginx', $options['WEB_SERVER']);
        $this->assertEquals('myapp', $options['MYSQL_DB']);
        $this->assertEquals('myapp', $options['MYSQL_USER']);
    }

    public function testBuildProvisionOptionsDerivesGitHostFromRepo(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'buildProvisionOptions');
        $method->setAccessible(true);

        $params = [
            'name' => 'production',
            'php_version' => '8.3',
            'db_name' => 'myapp',
            'db_user' => 'myapp',
            'timezone' => 'UTC',
        ];

        $options = $method->invoke($provisioner, $this->config['production'], $params);
        $this->assertEquals('github.com', $options['GIT_HOST']);
    }

    public function testBuildProvisionOptionsFallsBackToDefaultGitHost(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'buildProvisionOptions');
        $method->setAccessible(true);

        $env = ['host' => '1.2.3.4', 'key' => '~/.ssh/key', 'path' => '/var/www/app'];
        $params = [
            'name' => 'production',
            'php_version' => '8.3',
            'db_name' => 'myapp',
            'db_user' => 'myapp',
            'timezone' => 'UTC',
        ];

        $options = $method->invoke($provisioner, $env, $params);
        $this->assertEquals('github.com', $options['GIT_HOST']);
    }

    public function testBuildProvisionOptionsExtractsGitLabHost(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'buildProvisionOptions');
        $method->setAccessible(true);

        $env = [
            'host' => '1.2.3.4',
            'key' => '~/.ssh/key',
            'path' => '/var/www/app',
            'repo' => 'git@gitlab.com:group/project.git',
        ];
        $params = [
            'name' => 'production',
            'php_version' => '8.3',
            'db_name' => 'myapp',
            'db_user' => 'myapp',
            'timezone' => 'UTC',
        ];

        $options = $method->invoke($provisioner, $env, $params);
        $this->assertEquals('gitlab.com', $options['GIT_HOST']);
    }

    // ─────────────────────────────────────────────
    // generateScript (via reflection)
    // ─────────────────────────────────────────────

    public function testGenerateScriptCreatesTempFile(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'generateScript');
        $method->setAccessible(true);

        $options = [
            'SERVER_NAME' => 'test',
            'PHP_VERSION' => '8.3',
        ];

        $path = $method->invoke($provisioner, $options);

        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);

        $content = file_get_contents($path);
        $this->assertStringStartsWith("#!/bin/bash\n\n", $content);
        $this->assertStringContainsString("export SERVER_NAME='test'", $content);
        $this->assertStringContainsString("export PHP_VERSION='8.3'", $content);

        // Cleanup
        @unlink($path);
    }

    public function testGenerateScriptEscapesSingleQuotes(): void
    {
        $provisioner = new Provisioner($this->config);
        $method = new \ReflectionMethod($provisioner, 'generateScript');
        $method->setAccessible(true);

        $options = ['DB_NAME' => "it's a test"];
        $path = $method->invoke($provisioner, $options);

        $content = file_get_contents($path);
        $this->assertStringContainsString("export DB_NAME='it'\\''s a test'", $content);

        @unlink($path);
    }
}
