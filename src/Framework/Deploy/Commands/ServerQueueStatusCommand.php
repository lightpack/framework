<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Check the queue worker daemon status on a remote server.
 *
 * Usage:
 *   php console server:queue:status production
 *   php console server:queue:status production --name=emails
 */
class ServerQueueStatusCommand extends Command
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

        $name = $this->args->get('name');

        if (empty($name)) {
            $this->output->newline();
            $this->output->info("Checking queue worker status on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $name = $this->askWithDefault('Worker name', $env);
        }

        $this->output->info("Checking queue worker [{$name}] ...");
        $this->output->newline();

        $sshCommand = $this->buildSshCommand($envConfig, "sudo lp-supervisorctl status lightpack-{$name}:*");

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Failed to check queue worker [{$name}] status (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function askWithDefault(string $question, string $default): string
    {
        $input = trim((string) $this->prompt->ask("  {$question} [{$default}]"));
        return $input !== '' ? $input : $default;
    }
}
