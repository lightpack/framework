<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Stop the queue worker daemon on a remote server.
 *
 * Usage:
 *   php lightpack server:queue:stop production
 */
class ServerQueueStopCommand extends Command
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

        $this->output->info("Stopping queue worker on {$env} ...");
        $this->output->newline();

        $remoteScript = "cd {$appPath} && php console queue:stop";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker stopped on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to stop queue worker (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
