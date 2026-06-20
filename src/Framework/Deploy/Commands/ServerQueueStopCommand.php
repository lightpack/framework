<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Stop the queue worker daemon on a remote server.
 *
 * Usage:
 *   php console server:queue:stop production
 *   php console server:queue:stop production --name=emails
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

        $name = $this->args->get('name');

        if (empty($name)) {
            $this->output->newline();
            $this->output->info("→ Stopping queue worker on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $name = $this->askWithDefault('Worker name', $env);
        }

        $this->output->info("→ Stopping queue worker [{$name}] ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, "sudo lp-supervisorctl stop lightpack-{$name}:*");

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("✓ Queue worker [{$name}] stopped on {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to stop queue worker [{$name}] (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

}
