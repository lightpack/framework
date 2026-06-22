<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;
use Lightpack\Utils\Process;

/**
 * List all queue workers on a remote server.
 *
 * Usage:
 *   php console server:queue:list production
 */
class ServerQueueListCommand extends Command
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

        $this->output->info("→ Listing queue workers on {$env} ({$envConfig['host']})");
        $this->output->newline();

        $remoteScript = <<<'BASH'
set -e
for conf in /etc/supervisor/conf.d/lightpack-*.conf; do
    [ -e "$conf" ] || { echo "No queue workers found."; exit 0; }
    name=$(basename "$conf" .conf)
    sudo lp-supervisorctl status "${name}:*" 2>/dev/null || true
done
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $process = new Process;
        $output = '';
        $process->setTimeout(30)->execute($sshCommand, function (string $line) use (&$output) {
            $output .= $line;
        });

        $this->output->newline();

        if ($process->failed()) {
            $this->output->error("Failed to list queue workers (exit code: {$process->getExitCode()}).");

            return self::FAILURE;
        }

        $output = trim($output);

        if (empty($output) || $output === 'No queue workers found.') {
            $this->output->line('No queue workers found.');

            return self::SUCCESS;
        }

        $workers = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, 'lightpack-')) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2) {
                continue;
            }
            $name = explode(':', $parts[0])[0]; // lightpack-bye
            $name = substr($name, 10); // remove "lightpack-" prefix -> bye
            $status = $parts[1]; // RUNNING, STOPPED, etc.

            if (! isset($workers[$name])) {
                $workers[$name] = ['status' => $status, 'processes' => 0];
            }
            $workers[$name]['processes']++;
            if ($status !== 'RUNNING') {
                $workers[$name]['status'] = $status;
            }
        }

        if (empty($workers)) {
            $this->output->line('No queue workers found.');

            return self::SUCCESS;
        }

        $this->output->line('Workers');
        $this->output->line('  Name          Processes   Status');
        $this->output->line('  ─────────────────────────────────');

        foreach ($workers as $name => $info) {
            $procLabel = $info['processes'] === 1 ? '1 process ' : "{$info['processes']} processes";
            $this->output->line(sprintf("  %-13s %-11s %s", $name, $procLabel, $info['status']));
        }

        $this->output->newline();
        $this->output->info('→ Tip: php console server:queue:status ' . $env . ' --name=<worker>');

        return self::SUCCESS;
    }
}
