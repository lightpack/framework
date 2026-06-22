<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;

/**
 * View the last N lines of application logs on a remote server.
 *
 * Usage:
 *   php console server:logs:view production        View last 50 lines
 *   php console server:logs:view --lines=100       View last 100 lines
 *   php console server:logs:view --file=error.log  View a specific log file
 */
class LogsViewCommand extends Command
{
    use HasDeployConfigTrait;

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

        $lines = $this->args->get('lines');
        $logFile = $this->args->get('file');

        if ($lines === null || $logFile === null) {
            $this->output->newline();
            $this->output->info("→ Viewing logs on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $logFile = $logFile ?? $this->askWithDefault('Log file', 'lightpack.log');
            $lines = $lines ?? $this->askWithDefault('Lines to show', '50');
        }

        $lines = max(1, min((int) $lines, 1000));
        $logPath = $envConfig['path'] . '/storage/logs/' . $logFile;

        $this->output->info("→ Last {$lines} lines of {$logFile}:");
        $this->output->newline();

        $remoteScript = "tail -n {$lines} {$logPath} 2>&1";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 30);

        if (! $result['success']) {
            $this->output->newline();
            $this->output->error("Could not read logs (exit code: {$result['exit_code']}).");
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
