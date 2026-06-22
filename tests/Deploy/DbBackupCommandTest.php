<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\DbBackupCommand;
use PHPUnit\Framework\TestCase;

final class DbBackupCommandTest extends TestCase
{
    public function testBuildDumpScriptReadsEnvAndDumps(): void
    {
        $command = new DbBackupCommand;
        $method = new \ReflectionMethod($command, 'buildDumpScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app');

        $this->assertStringContainsString('set -e', $script);
        $this->assertStringContainsString('cd "/var/www/app"', $script);
        $this->assertStringContainsString('if [ ! -f .env ]', $script);
        $this->assertStringContainsString('read_env() {', $script);
        $this->assertStringContainsString('DB_HOST=$(read_env DB_HOST)', $script);
        $this->assertStringContainsString('DB_NAME=$(read_env DB_NAME)', $script);
        $this->assertStringContainsString('DB_USER=$(read_env DB_USER)', $script);
        $this->assertStringContainsString('DB_PASS=$(read_env DB_PSWD)', $script);
        $this->assertStringContainsString('mysqldump', $script);
        $this->assertStringContainsString('--single-transaction', $script);
        $this->assertStringContainsString('--routines', $script);
    }

    public function testBuildDumpScriptUsesMysqlPwdEnvVar(): void
    {
        $command = new DbBackupCommand;
        $method = new \ReflectionMethod($command, 'buildDumpScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app');

        $this->assertStringContainsString('export MYSQL_PWD="$DB_PASS"', $script);
        $this->assertStringContainsString('unset MYSQL_PWD', $script);
    }

    public function testBuildDumpScriptHandlesMissingEnv(): void
    {
        $command = new DbBackupCommand;
        $method = new \ReflectionMethod($command, 'buildDumpScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app');

        $this->assertStringContainsString('if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]', $script);
        $this->assertStringContainsString('exit 1', $script);
    }
}
