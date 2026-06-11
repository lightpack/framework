<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Restart the queue worker daemon on a remote server.
 *
 * Usage:
 *   php lightpack server:queue:restart production
 *   php lightpack server:queue:restart --queue=mail
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

        $appPath = $envConfig['path'];
        $queue = $this->args->get('queue') ?? 'default';

        $this->output->info("Restarting queue worker on {$env} ...");
        $this->output->newline();

        $remoteScript = "cd {$appPath} && php console queue:restart --queue={$queue}";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

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
