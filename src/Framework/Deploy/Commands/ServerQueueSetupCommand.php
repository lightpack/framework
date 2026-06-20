<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Install the queue worker as a supervised process on a remote server.
 *
 * Creates a supervisor config for the Lightpack queue worker and registers it.
 * Run this once after provisioning. Then use server:queue:start/stop/restart.
 *
 * Usage:
 *   php console server:queue:setup production
 *   php console server:queue:setup production --name=emails --queue=emails --workers=2
 *   php console server:queue:setup production --name=reports --queue=reports --workers=1 --stop-wait=300
 *   php console server:queue:setup production --workers=4 --cooldown=3600
 */
class ServerQueueSetupCommand extends Command
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

        $appPath = $envConfig['path'];
        $name     = $this->args->get('name');
        $queue    = $this->args->get('queue');
        $workers  = $this->args->get('workers');
        $cooldown = $this->args->get('cooldown');
        $stopWait = $this->args->get('stop-wait');

        if ($name === null || $queue === null || $workers === null || $cooldown === null || $stopWait === null) {
            $this->output->newline();
            $this->output->info("Installing queue worker on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $name     = $name     ?? $this->askWithDefault('Worker name', $env);
            $queue    = $queue    ?? $this->askWithDefault('Queue name', 'default');
            $workers  = $workers  ?? $this->askWithDefault('Number of workers', '1');
            $cooldown = $cooldown  ?? $this->askWithDefault('Cooldown (seconds)', '3600');
            $stopWait = $stopWait  ?? $this->askWithDefault('Stop wait (seconds)', '60');
        }

        $workers  = (int) $workers;
        $cooldown = (int) $cooldown;
        $stopWait = (int) $stopWait;

        $this->output->info("Installing queue worker [{$name}] ...");
        $this->output->newline();

        $supervisorConfig = $this->buildSupervisorConfig($name, $appPath, $queue, $workers, $cooldown, $stopWait);

        $remoteScript = <<<BASH
set -e

printf '%s' {$supervisorConfig} | sudo lp-supervisor-write {$name}
sudo supervisorctl reread
sudo supervisorctl update

echo "Queue worker [{$name}] registered with supervisor."
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Queue worker [{$name}] installed. Run: php console server:queue:start {$env} --name={$name}");
            return self::SUCCESS;
        }

        $this->output->error("Setup failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function askWithDefault(string $question, string $default): string
    {
        $input = trim((string) $this->prompt->ask("  {$question} [{$default}]"));
        return $input !== '' ? $input : $default;
    }

    private function buildSupervisorConfig(string $name, string $appPath, string $queue, int $workers, int $cooldown, int $stopWait): string
    {
        $programName = "lightpack-{$name}";

        $config = <<<INI
        [program:{$programName}]
        process_name=%(program_name)s_%(process_num)02d
        command=/usr/bin/env php {$appPath}/console jobs:run --queue={$queue} --cooldown={$cooldown}
        directory={$appPath}
        user=deploy
        numprocs={$workers}
        autostart=false
        autorestart=true
        stopasgroup=true
        killasgroup=true
        stopwaitsecs={$stopWait}
        redirect_stderr=true
        stdout_logfile=/var/log/supervisor/{$programName}.log
        INI;

        return escapeshellarg($config);
    }
}
