<?php

namespace Lightpack\DevOps;

use Lightpack\Utils\Process;

/**
 * Handles remote deployment via SSH.
 * Uses Lightpack\Utils\Process for robust process execution.
 */
class Deployer
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Deploy to the specified environment.
     *
     * Sequence: mkdir → git+composer → SCP .env → migrate+reload
     * The .env is copied after composer (which doesn't need it) but before
     * migrate:up (which needs DB credentials from .env).
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function deploy(string $environment, ?string $localEnvPath = null): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $timeout = $env['timeout'] ?? 300;

        // Step 1: Ensure app directory exists
        $this->execute($this->buildSshCommand($env, 'mkdir -p ' . escapeshellarg($env['path'])), 30);

        // Step 2: Pull code + install dependencies
        $codeResult = $this->execute($this->buildSshCommand($env, $this->buildCodeScript($env)), $timeout);

        if (!$codeResult['success']) {
            return $codeResult;
        }

        // Step 3: Copy .env (after composer, before migrate)
        if ($localEnvPath !== null && file_exists($localEnvPath)) {
            $scpResult = $this->execute($this->buildScpCommand($env, $localEnvPath), 60);

            if (!$scpResult['success']) {
                return $scpResult;
            }
        }

        // Step 4: Run migrations + reload services
        return $this->execute($this->buildSshCommand($env, $this->buildActivateScript($env)), $timeout);
    }

    /**
     * Rollback to a previous commit on the specified environment.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function rollback(string $environment, int $steps = 1): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $remoteScript = $this->buildRollbackScript($env, $steps);
        $sshCommand = $this->buildSshCommand($env, $remoteScript);
        $timeout = $env['timeout'] ?? 300;

        return $this->execute($sshCommand, $timeout);
    }

    /**
     * Copy a local .env file to the remote server.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function syncEnv(string $environment, string $localEnvPath): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $scpCommand = $this->buildScpCommand($env, $localEnvPath);
        $timeout = $env['timeout'] ?? 60;

        return $this->execute($scpCommand, $timeout);
    }

    /**
     * Get configured environment names.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->config['environments'] ?? []);
    }

    private function buildCodeScript(array $env): string
    {
        $path = $env['path'];
        $branch = $env['branch'] ?? 'main';
        $repo = $env['repo'] ?? null;

        $cloneOrFetch = $repo
            ? "if [ -d {path}/.git ]; then git -C {path} fetch origin {branch} && git -C {path} reset --hard origin/{branch}; else git -C {path} init -b {branch} && git -C {path} remote add origin {$repo} && git -C {path} fetch origin {branch} && git -C {path} reset --hard origin/{branch}; fi"
            : 'git -C {path} fetch origin {branch} && git -C {path} reset --hard origin/{branch}';

        $commands = [
            $cloneOrFetch,
            'composer -d {path} install --no-dev --optimize-autoloader',
        ];

        return str_replace(
            ['{path}', '{branch}'],
            [$path, $branch],
            implode(' && ', $commands)
        );
    }

    private function buildActivateScript(array $env): string
    {
        $path = $env['path'];
        $phpVersion = $env['php_version'] ?? '8.3';

        $commands = [
            'php {path}/console migrate:up --force',
            "sudo systemctl reload php{$phpVersion}-fpm",
        ];

        return str_replace(
            '{path}',
            $path,
            implode(' && ', $commands)
        );
    }

    private function buildRollbackScript(array $env, int $steps): string
    {
        $path = $env['path'];

        $commands = [
            "cd {$path}",
            'echo "Recent commits:"',
            'git log --oneline -5',
            "git reset --hard HEAD~{$steps}",
            'composer install --no-dev --optimize-autoloader',
            'echo ""',
            'echo "Rolled back. Current commit:"',
            'git log --oneline -1',
        ];

        return implode(' && ', $commands);
    }

    /**
     * Build SSH command as an array so Process can escape each argument correctly.
     */
    private function buildSshCommand(array $env, string $remoteScript): array
    {
        $user = $env['user'];
        $host = $env['host'];
        $key = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');

        return [
            'ssh',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "{$user}@{$host}",
            $remoteScript,
        ];
    }

    /**
     * Build SCP command as an array to copy local .env file to remote server.
     */
    private function buildScpCommand(array $env, string $localPath): array
    {
        $user = $env['user'];
        $host = $env['host'];
        $key = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');
        $remotePath = $env['path'] . '/.env';

        return [
            'scp',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            $localPath,
            "{$user}@{$host}:{$remotePath}",
        ];
    }

    private function resolveKeyPath(string $key): string
    {
        if (strpos($key, '~') === 0) {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? '';
            return str_replace('~', $home, $key);
        }

        return $key;
    }

    /**
     * Execute the SSH command using Process utility.
     *
     * @param string|array $command
     * @return array{success: bool, exit_code: int, output: string}
     */
    private function execute(string|array $command, int $timeout = 300): array
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
}
