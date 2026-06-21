<?php

namespace Lightpack\Deploy;

use Lightpack\Utils\Process;

/**
 * Shared helpers for deploy commands that operate on remote servers.
 */
trait HasDeployConfigTrait
{
    /**
     * Load config/deploy.php and return the ['deploy'] sub-array.
     *
     * Returns null and prints an error if the file is missing or malformed.
     */
    private function loadConfig(): ?array
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Run: php console create:config --support=deploy');
            return null;
        }

        $raw = require $configPath;

        if (!isset($raw['deploy']) || !is_array($raw['deploy'])) {
            $this->output->error('Invalid config/deploy.php: missing "deploy" key.');
            $this->output->newline();
            $this->output->line('Run: php console create:config --support=deploy');
            return null;
        }

        return $raw['deploy'];
    }

    /**
     * Resolve the target environment.
     *
     * Defaults to 'production' when no argument is provided.
     */
    private function resolveEnvironment(array $config): string
    {
        return $this->args->argument(0) ?: 'production';
    }

    private function getEnvConfig(array $config, string $env): ?array
    {
        return $config[$env] ?? null;
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

        foreach (array_keys($config) as $name) {
            $this->output->line("  - {$name}");
        }

        $this->output->newline();
    }

    /**
     * Build an SSH command array for executing a remote script.
     *
     * User is always 'deploy' — created by server:provision.
     * Key must be explicitly set in config/deploy.php.
     */
    private function buildSshCommand(array $envConfig, string $remoteScript): array
    {
        $host = $envConfig['host'];
        $key  = $this->resolveKeyPath($envConfig['key']);

        return [
            'ssh',
            '-n',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "deploy@{$host}",
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
            'success'   => $exitCode === 0,
            'exit_code' => $exitCode,
            'output'    => $output,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Validate a domain name to prevent path traversal in server-side paths.
     */
    private function validateDomain(string $domain): bool
    {
        if (strpos($domain, '..') !== false) {
            return false;
        }

        if (strpbrk($domain, '/\\$`;&|<>"\'') !== false) {
            return false;
        }

        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9][-a-zA-Z0-9]*(\.[a-zA-Z0-9][-a-zA-Z0-9]*)+$/', $domain) === 1;
    }

    private function ask(string $question): string
    {
        $this->output->line("  {$question}");
        return trim((string) $this->prompt->ask("  › "));
    }

    private function askWithDefault(string $question, string $default): string
    {
        $this->output->line("  {$question} [{$default}]");
        $input = trim((string) $this->prompt->ask("  › "));
        return $input !== '' ? $input : $default;
    }

    private function askOrNull(string $question, ?string $default = null): ?string
    {
        if ($default !== null) {
            $this->output->line("  {$question} [{$default}]");
        } else {
            $this->output->line("  {$question}");
        }
        $input = trim((string) $this->prompt->ask("  › "));
        if ($input === '') {
            return $default;
        }
        return $input;
    }

    private function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $this->output->line("  {$question} [{$defaultText}]");
        $input = strtolower(trim((string) $this->prompt->ask("  › ")));
        if ($input === '') {
            return $default;
        }
        return $input === 'y' || $input === 'yes';
    }
}
