<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;

/**
 * Drop a MySQL database and/or user on a remote server.
 *
 * Destructive operation — requires typing 'yes' to confirm.
 * Either --db or --user (or both) must be provided.
 *
 * Uses the lp-mysql-drop wrapper (installed during provisioning) which runs
 * MySQL via root socket auth — no root password required.
 *
 * Usage:
 *   php console db:drop production --db=shopdb
 *   php console db:drop production --user=shopuser
 *   php console db:drop production --db=shopdb --user=shopuser
 */
class DbDropCommand extends Command
{
    use HasDeployConfigTrait;

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

        if (empty($dbName) && empty($dbUser)) {
            $this->output->newline();
            $this->output->info("→ Dropping database or user on {$env} ({$envConfig['host']})");
            $this->output->newline();
            $this->output->error('Provide at least one of --db=<name> or --user=<name>.');

            return self::FAILURE;
        }

        if (! empty($dbName) && ! preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            $this->output->error('Database name may only contain letters, numbers, and underscores.');

            return self::FAILURE;
        }

        if (! empty($dbUser) && ! preg_match('/^[a-zA-Z0-9_]+$/', $dbUser)) {
            $this->output->error('Username may only contain letters, numbers, and underscores.');

            return self::FAILURE;
        }

        // Security confirmation
        $this->output->warning('→ DATABASE DROP');
        $this->output->newline();
        $this->output->line("Environment: {$env}");

        if (! empty($dbName)) {
            $this->output->line("Database:    {$dbName}");
        }
        if (! empty($dbUser)) {
            $this->output->line("User:        {$dbUser}");
        }

        $this->output->newline();
        $this->output->line("  This is DESTRUCTIVE. Are you sure? (type 'yes' to confirm)");
        $response = strtolower(trim((string) $this->prompt->ask("  › ")));

        if ($response !== 'yes') {
            $this->output->line('Drop cancelled.');

            return self::FAILURE;
        }

        $this->output->newline();
        $this->output->info("→ Dropping on {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        $remoteScript = $this->buildDropScript($dbName, $dbUser);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if (! $result['success']) {
            $this->output->error("Failed to drop (exit code: {$result['exit_code']}).");

            return self::FAILURE;
        }

        $dbDropped = strpos($result['output'], 'DB_DROPPED:') !== false;
        $dbNotFound = strpos($result['output'], 'DB_NOT_FOUND:') !== false;
        $userDropped = strpos($result['output'], 'USER_DROPPED:') !== false;
        $userNotFound = strpos($result['output'], 'USER_NOT_FOUND:') !== false;

        if (! empty($dbName)) {
            if ($dbDropped) {
                $this->output->success("✓ Database '{$dbName}' dropped.");
            } else {
                $this->output->warning("→ Database '{$dbName}' not found.");
            }
        }

        if (! empty($dbUser)) {
            if ($userDropped) {
                $this->output->success("✓ User '{$dbUser}' dropped.");
            } else {
                $this->output->warning("→ User '{$dbUser}' not found.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build the remote bash script.
     *
     * lp-mysql-drop is installed by provisioning and runs MySQL via root
     * socket auth — no password ever needs to leave the local machine.
     */
    private function buildDropScript(?string $dbName, ?string $dbUser): string
    {
        $args = '';

        if (! empty($dbName)) {
            $args .= ' --db=' . escapeshellarg($dbName);
        }

        if (! empty($dbUser)) {
            $args .= ' --user=' . escapeshellarg($dbUser);
        }

        return <<<BASH
set -e
sudo lp-mysql-drop{$args}
BASH;
    }
}
