<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Remove the Lightpack scheduler cron job from a remote server.
 *
 * Removes the cron entry by matching the lightpack-scheduler marker.
 * No sudo required.
 *
 * Usage:
 *   php lightpack schedule:remove production
 */
class ScheduleRemoveCommand extends Command
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

        $this->output->info("Removing scheduler from {$env} ...");
        $this->output->newline();

        $remoteScript = <<<BASH
set -e

# Check if installed
if ! crontab -l 2>/dev/null | grep -q '{$marker}'; then
    echo "Scheduler is not installed."
    exit 0
fi

# Remove lines between (and including) the marker comments
# This removes the marker lines and the cron entry between them
crontab -l 2>/dev/null | sed '/# {$marker}/,/# {$marker}-end/d' | crontab -

echo "Scheduler removed."
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success('Scheduler removed.');
            return self::SUCCESS;
        }

        $this->output->error("Failed to remove scheduler (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
