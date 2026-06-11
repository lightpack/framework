<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Check the queue worker daemon status on a remote server.
 *
 * Usage:
 *   php console server:queue:status production
 */
class ServerQueueStatusCommand extends Command
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

        $this->output->info("Checking queue worker on {$env} ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, 'sudo supervisorctl status lightpack-worker:*');

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Failed to check queue status (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
