<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\Utils\Process;

/**
 * Pull the remote .env file from a server for inspection.
 *
 * Downloads the remote environment file to storage/env/<env>.env.
 * The file is created with 0600 permissions to protect secrets.
 *
 * Usage:
 *   php console env:pull production
 *   php console env:pull --output=production-backup.env
 */
class EnvPullCommand extends Command
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
        $outputName = $this->args->get('output') ?? "{$env}.env";
        $localDir = DIR_ROOT . '/storage/env';
        $localPath = $localDir . '/' . $outputName;

        $this->output->warning('This file contains secrets. Handle with care.');
        $this->output->newline();
        $this->output->info("Pulling .env from {$env} ...");
        $this->output->newline();

        // Download via SSH cat (avoids SCP complexities with special characters in paths)
        $remoteScript = "cat {$appPath}/.env";
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $process = new Process();
        $envContent = '';

        $process
            ->setTimeout(30)
            ->execute($sshCommand, function (string $line, string $type) use (&$envContent) {
                if ($type === 'stdout') {
                    $envContent .= $line;
                }
            });

        $exitCode = $process->getExitCode() ?? -1;

        if ($exitCode !== 0 || empty($envContent)) {
            $this->output->newline();
            $this->output->error("Failed to pull .env from {$env}.");
            return self::FAILURE;
        }

        // Ensure directory exists
        if (!is_dir($localDir)) {
            mkdir($localDir, 0750, true);
        }

        file_put_contents($localPath, $envContent);
        chmod($localPath, 0600);

        $size = $this->formatBytes(strlen($envContent));
        $this->output->success("Saved to storage/env/{$outputName} ({$size})");

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
