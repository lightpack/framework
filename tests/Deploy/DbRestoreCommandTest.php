<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\DbRestoreCommand;
use PHPUnit\Framework\TestCase;

final class DbRestoreCommandTest extends TestCase
{
    public function testBuildRestoreScriptReadsEnvAndImports(): void
    {
        $command = new DbRestoreCommand();
        $method = new \ReflectionMethod($command, 'buildRestoreScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app', '/tmp/restore-backup.sql');

        $this->assertStringContainsString('set -e', $script);
        $this->assertStringContainsString('cd "/var/www/app"', $script);
        $this->assertStringContainsString('if [ ! -f .env ]', $script);
        $this->assertStringContainsString('read_env() {', $script);
        $this->assertStringContainsString('mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME"', $script);
        $this->assertStringContainsString('"/tmp/restore-backup.sql"', $script);
    }

    public function testBuildRestoreScriptUsesMysqlPwdEnvVar(): void
    {
        $command = new DbRestoreCommand();
        $method = new \ReflectionMethod($command, 'buildRestoreScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app', '/tmp/dump.sql');

        $this->assertStringContainsString('export MYSQL_PWD="$DB_PASS"', $script);
        $this->assertStringContainsString('unset MYSQL_PWD', $script);
    }

    public function testBuildRestoreScriptHandlesMissingEnv(): void
    {
        $command = new DbRestoreCommand();
        $method = new \ReflectionMethod($command, 'buildRestoreScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app', '/tmp/dump.sql');

        $this->assertStringContainsString('if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]', $script);
        $this->assertStringContainsString('exit 1', $script);
    }

    public function testBuildRestoreScriptOutputsSuccess(): void
    {
        $command = new DbRestoreCommand();
        $method = new \ReflectionMethod($command, 'buildRestoreScript');
        $method->setAccessible(true);

        $script = $method->invoke($command, '/var/www/app', '/tmp/dump.sql');

        $this->assertStringContainsString('Database restored successfully', $script);
    }

}
