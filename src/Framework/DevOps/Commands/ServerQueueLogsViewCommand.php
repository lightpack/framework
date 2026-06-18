<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * View the last N lines of queue worker logs from a remote server.
 *
 * The supervisor config writes worker stdout to /var/log/supervisor/lightpack-{name}.log.
 *
 * Usage:
 *   php console server:queue:logs:view production
 *   php console server:queue:logs:view production --name=emails
 *   php console server:queue:logs:view production --lines=100
 */
class ServerQueueLogsViewCommand extends Command
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

        $name    = $this->args->get('name') ?? $env;
        $lines   = (int) ($this->args->get('lines') ?? 50);
        $lines   = max(1, min($lines, 1000));
        $logFile = "/var/log/supervisor/lightpack-{$name}.log";

        $this->output->info("Last {$lines} lines of queue worker [{$name}] logs on {$env}:");
        $this->output->newline();

        $remoteScript = <<<BASH
if [ -r "{$logFile}" ]; then
    tail -n {$lines} "{$logFile}"
elif sudo -n tail -n {$lines} "{$logFile}" 2>/dev/null; then
    :
else
    echo "ERROR: Cannot read {$logFile}" >&2
    echo "Either make it readable (chmod 644) or configure passwordless sudo:" >&2
    echo "  echo 'deploy ALL=(root) NOPASSWD: /usr/bin/tail /var/log/supervisor/lightpack-*.log' | sudo tee /etc/sudoers.d/lightpack-logs" >&2
    exit 1
fi
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result     = $this->executeRemote($sshCommand, 30);

        if (!$result['success']) {
            $this->output->newline();
            $this->output->error("Could not view queue worker [{$name}] logs (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
