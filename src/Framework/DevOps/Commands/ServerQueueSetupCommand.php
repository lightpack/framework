<?php

namespace Lightpack\DevOps\Commands;

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

        $appPath = $envConfig['app']['path'];
        $phpVersion = $envConfig['php'] ?? '8.3';
        $user = $envConfig['user'];
        $name = $this->args->get('name') ?? 'worker';
        $queue = $this->args->get('queue') ?? 'default';
        $workers = (int) ($this->args->get('workers') ?? 1);
        $cooldown = (int) ($this->args->get('cooldown') ?? 3600);
        $stopWait = (int) ($this->args->get('stop-wait') ?? 60);

        $this->output->info("Installing queue worker [{$name}] on {$env} ...");
        $this->output->newline();

        $supervisorConfig = $this->buildSupervisorConfig($name, $appPath, $phpVersion, $user, $queue, $workers, $cooldown, $stopWait);

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

    private function buildSupervisorConfig(string $name, string $appPath, string $phpVersion, string $user, string $queue, int $workers, int $cooldown, int $stopWait): string
    {
        $programName = "lightpack-{$name}";

        $config = <<<INI
        [program:{$programName}]
        process_name=%(program_name)s_%(process_num)02d
        command=/usr/bin/php{$phpVersion} {$appPath}/console jobs:run --queue={$queue} --cooldown={$cooldown}
        directory={$appPath}
        user={$user}
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
