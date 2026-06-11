<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Run an arbitrary command on the remote server from the app directory.
 *
 * Usage:
 *   php console server:run production --cmd="php console some:commqnd"
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
            $this->output->error('Command is required. Use --cmd="php console some:command"');
            return self::FAILURE;
        }

        $appPath = $envConfig['path'];
        $timeout = $envConfig['timeout'] ?? 120;

        $remoteScript = "cd " . escapeshellarg($appPath) . " && {$cmd}";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $this->output->info("Running on {$env}: {$cmd}");
        $this->output->newline();

        $result = $this->executeRemote($sshCommand, $timeout);

        $this->output->newline();

        if (!empty($result['output'])) {
            $this->output->line($result['output']);
        }

        if ($result['success']) {
            return self::SUCCESS;
        }

        $this->output->error("Command failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
