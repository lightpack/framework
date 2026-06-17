<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Install the Lightpack scheduler cron job on a remote server.
 *
 * Adds a single line to the deploy user's crontab that runs
 * schedule:events every minute. No sudo required.
 *
 * Usage:
 *   php console server:schedule:setup production
 */
class ScheduleSetupCommand extends Command
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

        $appPath = $envConfig['app']['path'];
        $cronLine = "* * * * * cd {$appPath} && php console schedule:events >> /dev/null 2>&1";
        $marker = 'lightpack-scheduler';

        $this->output->info("Installing scheduler on {$env} ...");
        $this->output->newline();

        $remoteScript = <<<BASH
set -e

# Check if already installed
if crontab -l 2>/dev/null | grep -q '{$marker}'; then
    echo "Scheduler is already installed."
    exit 0
fi

# Add the cron job with a marker comment for easy identification
(crontab -l 2>/dev/null || true; echo "# {$marker}"; echo "{$cronLine}"; echo "# {$marker}-end") | crontab -

echo "Scheduler installed."
echo "Cron entry: {$cronLine}"
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success('Scheduler installed.');
            return self::SUCCESS;
        }

        $this->output->error("Failed to install scheduler (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
