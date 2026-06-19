<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Restart the supervised queue worker on a remote server.
 *
 * Usage:
 *   php console server:queue:restart production
 *   php console server:queue:restart production --name=emails
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

        $name = $this->args->get('name') ?? $env;

        $this->output->info("Restarting queue worker [{$name}] on {$env} ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, "sudo lp-supervisorctl restart lightpack-{$name}:*");

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker [{$name}] restarted on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to restart queue worker [{$name}] (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
