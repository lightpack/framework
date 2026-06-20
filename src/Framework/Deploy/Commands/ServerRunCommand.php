<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Run an arbitrary command on the remote server from the app directory.
 *
 * Usage:
 *   php console server:run production --cmd="php console some:command"
 */
class ServerRunCommand extends Command
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

        $cmd = $this->args->get('cmd');

        if (empty($cmd)) {
            $this->output->newline();
            $this->output->info("→ Running command on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $cmd = $this->ask('Command');

            if (empty($cmd)) {
                $this->output->error('Command cannot be empty.');
                return self::FAILURE;
            }
        }

        $appPath = $envConfig['path'];

        $remoteScript = "cd " . escapeshellarg($appPath) . " && {$cmd}";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $this->output->info("→ Running on {$env}: {$cmd}");
        $this->output->newline();

        $result = $this->executeRemote($sshCommand, 120);

        $this->output->newline();

        if ($result['success']) {
            return self::SUCCESS;
        }

        $this->output->error("Command failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
