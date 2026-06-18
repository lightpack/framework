<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Stream queue worker logs in real-time from a remote server.
 *
 * The supervisor config writes worker stdout to /var/log/supervisor/lightpack-{name}.log.
 *
 * Usage:
 *   php console server:queue:logs:tail production
 *   php console server:queue:logs:tail production --name=emails
 */
class ServerQueueLogsTailCommand extends Command
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
        $logFile = "/var/log/supervisor/lightpack-{$name}.log";

        $this->output->info("Tailing queue worker [{$name}] logs on {$env} (Ctrl+C to stop) ...");
        $this->output->newline();

        $remoteScript = <<<BASH
if [ -r "{$logFile}" ]; then
    tail -f "{$logFile}"
elif sudo -n tail -f "{$logFile}" 2>/dev/null; then
    :
else
    echo "ERROR: Cannot read {$logFile}" >&2
    echo "Either make it readable (chmod 644) or configure passwordless sudo:" >&2
    echo "  echo 'deploy ALL=(root) NOPASSWD: /usr/bin/tail /var/log/supervisor/lightpack-*.log' | sudo tee /etc/sudoers.d/lightpack-logs" >&2
    exit 1
fi
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result     = $this->executeRemote($sshCommand, 86400);

        if (!$result['success']) {
            $this->output->newline();
            $this->output->error("Could not tail queue worker [{$name}] logs (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
