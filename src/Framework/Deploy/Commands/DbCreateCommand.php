<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Create a new MySQL database and user on a remote server.
 *
 * Designed for adding a second (or third) application to an already-provisioned
 * server. Each application should have its own dedicated database and user.
 *
 * Uses the lp-mysql-create wrapper (installed during provisioning) which runs
 * MySQL via root socket auth — no root password required.
 *
 * A secure random password is generated for the new database user automatically.
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
        $dbUser = $this->args->get('user');

        if (empty($dbName)) {
            $this->output->newline();
            $this->output->info("Creating database on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $dbName = $this->ask('Database name');
        }

        if (empty($dbName) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            $this->output->error('Database name may only contain letters, numbers, and underscores.');
            return self::FAILURE;
        }

        $dbUser = $dbUser ?? $this->askWithDefault('Database user', $dbName);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbUser)) {
            $this->output->error('Username may only contain letters, numbers, and underscores.');
            return self::FAILURE;
        }

        $dbPass = bin2hex(random_bytes(12));

        $this->output->info("Creating database [{$dbName}] on {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        $remoteScript = $this->buildCreateScript($dbName, $dbUser, $dbPass);
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
     * Build the remote bash script.
     *
     * lp-mysql-create is installed by provisioning and runs MySQL via root
     * socket auth — no password ever needs to leave the local machine.
     */
    private function ask(string $question): string
    {
        return trim((string) $this->prompt->ask("  {$question}"));
    }

    private function askWithDefault(string $question, string $default): string
    {
        $input = trim((string) $this->prompt->ask("  {$question} [{$default}]"));
        return $input !== '' ? $input : $default;
    }

    private function buildCreateScript(string $dbName, string $dbUser, string $dbPass): string
    {
        $nameArg = escapeshellarg($dbName);
        $userArg = escapeshellarg($dbUser);
        $passArg = escapeshellarg($dbPass);

        return <<<BASH
set -e
sudo lp-mysql-create {$nameArg} {$userArg} {$passArg}
echo "Database [{$dbName}] created, user [{$dbUser}] granted access."
BASH;
    }
}
