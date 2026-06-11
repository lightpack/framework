<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Restart the supervised queue worker on a remote server.
 *
 * Usage:
 *   php console server:queue:restart production
 */
class ServerQueueRestartCommand extends Command
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

        $this->output->info("Restarting queue worker on {$env} ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, 'sudo supervisorctl restart lightpack-worker:*');

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker restarted on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to restart queue worker (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
