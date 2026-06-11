<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * View the last N lines of application logs on a remote server.
 *
 * Usage:
 *   php lightpack logs:view production        View last 50 lines
 *   php lightpack logs:view --lines=100       View last 100 lines
 *   php lightpack logs:view --file=error.log  View a specific log file
 */
class LogsViewCommand extends Command
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

        $lines = (int) ($this->args->get('lines') ?? 50);
        $lines = max(1, min($lines, 1000));

        $logFile = $this->args->get('file') ?? 'lightpack.log';
        $logPath = $envConfig['path'] . '/storage/logs/' . $logFile;

        $this->output->info("Last {$lines} lines of {$logFile} on {$env}:");
        $this->output->newline();

        $remoteScript = "tail -n {$lines} {$logPath} 2>&1";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 30);

        if (!$result['success']) {
            $this->output->newline();
            $this->output->error("Could not read logs (exit code: {$result['exit_code']}).");
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
