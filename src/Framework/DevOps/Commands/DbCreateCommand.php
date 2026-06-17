<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Create a new MySQL database and user on a remote server.
 *
 * Designed for adding a second (or third) application to an already-provisioned
 * server. Each application should have its own dedicated database and user.
 *
 * The MySQL root password is read automatically from deploy/credentials/<env>.txt
 * if available, otherwise you will be prompted to enter it.
 *
 * A secure random password is generated for the new database user.
 *
 * Usage:
 *   php console db:create production --db=shopdb
 *   php console db:create production --db=shopdb --user=shopuser
 */
class DbCreateCommand extends Command
{
    use HasDeployConfig;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $dbName = $this->args->get('db');

        if (empty($dbName)) {
            $this->output->error('Database name is required. Use --db=mydbname');
            return self::FAILURE;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            $this->output->error('Database name may only contain letters, numbers, and underscores.');
            return self::FAILURE;
        }

        $dbUser = $this->args->get('user') ?? $dbName;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbUser)) {
            $this->output->error('Username may only contain letters, numbers, and underscores.');
            return self::FAILURE;
        }

        $rootPass = $this->resolveRootPassword($env);

        if ($rootPass === null) {
            return self::FAILURE;
        }

        $dbPass = bin2hex(random_bytes(12));

        $this->output->info("Creating database [{$dbName}] on {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        $remoteScript = $this->buildCreateScript($dbName, $dbUser, $dbPass, $rootPass);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Failed to create database (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        $this->output->success('Database created.');
        $this->output->newline();
        $this->output->info('Credentials — save these now, they will not be shown again:');
        $this->output->newline();
        $this->output->line("  DB_HOST: 127.0.0.1");
        $this->output->line("  DB_NAME: {$dbName}");
        $this->output->line("  DB_USER: {$dbUser}");
        $this->output->line("  DB_PSWD: {$dbPass}");
        $this->output->newline();
        $this->output->warning("Add these to your .env.{$env} file before deploying.");

        return self::SUCCESS;
    }

    /**
     * Try to read the MySQL root password from the local credentials file.
     * Falls back to prompting the user.
     */
    private function resolveRootPassword(string $env): ?string
    {
        $credFile = DIR_ROOT . '/deploy/credentials/' . $env . '.txt';

        if (file_exists($credFile)) {
            $contents = file_get_contents($credFile);

            if (preg_match('/MYSQL ROOT:.*?Password:\s+(\S+)/s', $contents, $matches)) {
                $this->output->line("MySQL root password read from deploy/credentials/{$env}.txt");
                $this->output->newline();
                return $matches[1];
            }
        }

        $this->output->line("Tip: MySQL root password is in deploy/credentials/{$env}.txt under \"MYSQL ROOT\".");
        $this->output->newline();
        $rootPass = $this->prompt->ask('MySQL root password');

        if (empty($rootPass)) {
            $this->output->error('MySQL root password is required.');
            return null;
        }

        return $rootPass;
    }

    /**
     * Build the remote bash script to create the database and user.
     *
     * MYSQL_PWD env var is used instead of -p flag to keep the password
     * out of the process argument list on the server.
     */
    private function buildCreateScript(string $dbName, string $dbUser, string $dbPass, string $rootPass): string
    {
        // Escape root password for embedding in a bash single-quoted string
        $rootPassBash = str_replace("'", "'\\''", $rootPass);

        return <<<BASH
set -e

export MYSQL_PWD='{$rootPassBash}'

mysql -u root << 'ENDSQL'
CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}';
GRANT ALL PRIVILEGES ON {$dbName}.* TO '{$dbUser}'@'localhost';
FLUSH PRIVILEGES;
ENDSQL

unset MYSQL_PWD

echo "Database [{$dbName}] created, user [{$dbUser}] granted access."
BASH;
    }
}
