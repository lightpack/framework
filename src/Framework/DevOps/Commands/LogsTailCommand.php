<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Stream application logs in real-time from a remote server.
 *
 * Usage:
 *   php lightpack logs:tail production          Tail default log file
 *   php lightpack logs:tail --file=error.log    Tail a specific log file
 */
class LogsTailCommand extends Command
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

        $logFile = $this->args->get('file') ?? 'lightpack.log';
        $logPath = $envConfig['path'] . '/storage/logs/' . $logFile;

        $this->output->info("Tailing {$logFile} on {$env} (Ctrl+C to stop) ...");
        $this->output->newline();

        $remoteScript = "tail -f {$logPath} 2>&1";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        // Long timeout for tail -f; user stops with Ctrl+C
        $result = $this->executeRemote($sshCommand, 86400);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
