<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Start the queue worker daemon on a remote server.
 *
 * SSHs into the server and runs queue:daemon on the remote machine.
 *
 * Usage:
 *   php console server:queue:start production
 *   php console server:queue:start --queue=mail
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

        $appPath = $envConfig['path'];
        $queue = $this->args->get('queue') ?? 'default';

        $this->output->info("Starting queue worker on {$env} ...");
        $this->output->newline();

        $remoteScript = "cd {$appPath} && php console queue:daemon --queue={$queue}";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker started on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to start queue worker (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
