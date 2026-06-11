<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Check if the Lightpack scheduler cron job is installed on a remote server.
 *
 * Usage:
 *   php console schedule:status production
 */
class ScheduleStatusCommand extends Command
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

        $marker = 'lightpack-scheduler';

        $this->output->info("Checking scheduler on {$env} ...");
        $this->output->newline();

        $remoteScript = <<<BASH
set -e

# Get crontab and check for our marker
CRON=$(crontab -l 2>/dev/null || true)

if echo "\$CRON" | grep -q '{$marker}'; then
    echo "STATUS: installed"
    echo ""
    echo "Matching entries:"
    echo "\$CRON" | grep -A1 '{$marker}' | grep -v '{$marker}'
else
    echo "STATUS: not-installed"
fi
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Failed to check status (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        if (str_contains($result['output'], 'STATUS: installed')) {
            $this->output->success('Scheduler is installed.');
        } else {
            $this->output->warning('Scheduler is not installed.');
            $this->output->newline();
            $this->output->line('Install with: php console schedule:setup ' . $env);
        }

        return self::SUCCESS;
    }
}
