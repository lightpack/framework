<?php

namespace Lightpack\Deploy;

/**
 * Handles remote deployment via SSH.
 */
class Deployer
{
    use RunsProcessTrait;

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Deploy to the specified environment.
     *
     * Sequence: mkdir → git+composer → SCP .env → link:storage → migrate+reload
     * The .env is copied after composer (which doesn't need it) but before
     * migrate:up (which needs DB credentials from .env).
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function deploy(string $environment, ?string $localEnvPath = null): array
    {
        $env = $this->config[$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        // Step 1: Ensure app directory exists
        $this->execute($this->buildSshCommand($env, 'mkdir -p ' . escapeshellarg($env['path'])), 30);

        // Step 2: Pull code + install dependencies
        $codeResult = $this->execute($this->buildSshCommand($env, $this->buildCodeScript($env)), 300);

        if (! $codeResult['success']) {
            return $codeResult;
        }

        // Step 3: Copy .env (after composer, before migrate)
        if ($localEnvPath !== null && file_exists($localEnvPath)) {
            $scpResult = $this->execute($this->buildScpCommand($env, $localEnvPath), 60);

            if (! $scpResult['success']) {
                return $scpResult;
            }
        }

        // Step 4: Run migrations + reload services
        return $this->execute($this->buildSshCommand($env, $this->buildActivateScript($env)), 300);
    }

    /**
     * Rollback to a previous commit on the specified environment.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function rollback(string $environment, int $steps = 1): array
    {
        $env = $this->config[$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $remoteScript = $this->buildRollbackScript($env, $steps);
        $sshCommand = $this->buildSshCommand($env, $remoteScript);

        return $this->execute($sshCommand, 300);
    }

    /**
     * Copy a local .env file to the remote server.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function syncEnv(string $environment, string $localEnvPath): array
    {
        $env = $this->config[$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $scpCommand = $this->buildScpCommand($env, $localEnvPath);

        return $this->execute($scpCommand, 60);
    }

    /**
     * Get configured environment names.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->config);
    }

    private function buildCodeScript(array $env): string
    {
        $rawPath = $env['path'];
        $path = escapeshellarg($rawPath);
        $branch = escapeshellarg($env['branch'] ?? 'main');
        $ref = escapeshellarg('origin/' . ($env['branch'] ?? 'main'));
        $repo = $env['repo'] ?? null;

        if ($repo !== null) {
            $repoSafe = escapeshellarg($repo);
            $ensureRepo = "test -d {$rawPath}/.git || git -C {$path} init";
            $syncRemote = "git -C {$path} remote set-url origin {$repoSafe} 2>/dev/null || git -C {$path} remote add origin {$repoSafe}";
            $pullCode = "git -C {$path} fetch origin {$branch} && git -C {$path} reset --hard {$ref}";
            $gitSteps = "{$ensureRepo} && {$syncRemote} && {$pullCode}";
        } else {
            $gitSteps = "git -C {$path} fetch origin {$branch} && git -C {$path} reset --hard {$ref}";
        }

        $composer = "composer -d {$path} install --no-dev --optimize-autoloader";

        return "{$gitSteps} && {$composer}";
    }

    private function buildActivateScript(array $env): string
    {
        $rawPath = $env['path'];
        $storagePath = escapeshellarg($rawPath . '/storage');
        $consolePath = escapeshellarg($rawPath . '/console');

        $commands = [
            "php {$consolePath} link:storage",
            "find {$storagePath} -type d -exec chmod 2775 {} \\; && find {$storagePath} -type d -exec chgrp www-data {} \\;",
            "find {$storagePath} -type f -exec chmod 664 {} \\; 2>/dev/null || true",
            "php {$consolePath} migrate:up --force",
        ];

        // Auto-detect installed PHP version on the server at deploy time.
        $commands[] = 'PHP_VER=$(php -r \'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;\' 2>/dev/null) && sudo systemctl reload php${PHP_VER}-fpm';

        return implode(' && ', $commands);
    }

    private function buildRollbackScript(array $env, int $steps): string
    {
        $path = escapeshellarg($env['path']);

        $commands = [
            "cd {$path}",
            "echo 'Recent commits:'",
            'git log --oneline -5',
            "git reset --hard HEAD~{$steps}",
            "composer -d {$path} install --no-dev --optimize-autoloader",
            "echo ''",
            "echo 'Rolled back. Current commit:'",
            'git log --oneline -1',
            'PHP_VER=$(php -r \'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;\' 2>/dev/null) && sudo systemctl reload php${PHP_VER}-fpm',
        ];

        return implode(' && ', $commands);
    }

    /**
     * Build SSH command as an array so Process can escape each argument correctly.
     */
    private function buildSshCommand(array $env, string $remoteScript): array
    {
        $host = $env['host'];
        $key = $this->resolveKeyPath($env['key']);

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
     * Build SCP command as an array to copy local .env file to remote server.
     */
    private function buildScpCommand(array $env, string $localPath): array
    {
        $host = $env['host'];
        $key = $this->resolveKeyPath($env['key']);
        $remotePath = $env['path'] . '/.env';

        return [
            'scp',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            $localPath,
            "deploy@{$host}:{$remotePath}",
        ];
    }
}
