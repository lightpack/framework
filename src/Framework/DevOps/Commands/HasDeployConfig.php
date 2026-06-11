<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\DevOps\Deployer;
use Lightpack\Utils\Process;

/**
 * Shared helpers for DevOps commands that operate on remote servers.
 */
trait HasDeployConfig
{
    private function loadConfig(): ?array
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Create config/deploy.php with your server settings.');
            return null;
        }

        return require $configPath;
    }

    private function resolveEnvironment(array $config): string
    {
        $argument = $this->args->argument(0);
        $defaultEnv = $config['default'] ?? 'production';
        return $argument ?: $defaultEnv;
    }

    private function getEnvConfig(array $config, string $env): ?array
    {
        return $config['environments'][$env] ?? null;
    }

    private function resolveKeyPath(string $key): string
    {
        if (strpos($key, '~') === 0) {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? '';
            return str_replace('~', $home, $key);
        }

        return $key;
    }

    private function printEnvironmentError(array $config, string $env): void
    {
        $this->output->error("Environment '{$env}' not found in config/deploy.php.");
        $this->output->newline();
        $this->output->line('Available environments:');

        $deployer = new Deployer($config);
        foreach ($deployer->getEnvironments() as $name) {
            $this->output->line("  - {$name}");
        }

        $this->output->newline();
    }

    /**
     * Build an SSH command array for executing a remote script.
     */
    private function buildSshCommand(array $envConfig, string $remoteScript): array
    {
        $user = $envConfig['user'];
        $host = $envConfig['host'];
        $key = $this->resolveKeyPath($envConfig['key'] ?? '~/.ssh/id_rsa');

        return [
            'ssh',
            '-n',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "{$user}@{$host}",
            $remoteScript,
        ];
    }

    /**
     * Execute a remote command and stream output in real-time.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    private function executeRemote(array $command, int $timeout = 300): array
    {
        $process = new Process();
        $output = '';

        $process
            ->setTimeout($timeout)
            ->execute($command, function (string $line, string $type) use (&$output) {
                $output .= $line;
                echo $line;
                flush();
            });

        $exitCode = $process->getExitCode() ?? -1;

        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }

    /**
     * Validate a domain name to prevent path traversal in server-side paths.
     */
    private function validateDomain(string $domain): bool
    {
        // Reject path traversal and shell metacharacters
        if (strpos($domain, '..') !== false) {
            return false;
        }

        if (strpbrk($domain, '/\\$`;&|<>"\'') !== false) {
            return false;
        }

        // Valid IPv4 address
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        // Valid domain: letters, digits, hyphens, dots
        return preg_match('/^[a-zA-Z0-9][-a-zA-Z0-9]*(\.[a-zA-Z0-9][-a-zA-Z0-9]*)+$/', $domain) === 1;
    }
}
