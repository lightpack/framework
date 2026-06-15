<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Start the supervised queue worker on a remote server.
 *
 * Usage:
 *   php console server:queue:start production
 *   php console server:queue:start production --name=emails
 */
class ServerQueueStartCommand extends Command
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

        $name = $this->args->get('name') ?? 'worker';

        $this->output->info("Starting queue worker [{$name}] on {$env} ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, "sudo lp-supervisorctl start lightpack-{$name}:*");
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker [{$name}] started on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to start queue worker [{$name}] (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
