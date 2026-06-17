<?php

namespace Lightpack\DevOps;

/**
 * Handles remote deployment via SSH.
 */
class Deployer
{
    use RunsProcess;

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
        $this->execute($this->buildSshCommand($env, 'mkdir -p ' . escapeshellarg($env['app']['path'])), 30);

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
        $app     = $env['app'] ?? [];
        $rawPath = $app['path'];
        $path    = escapeshellarg($rawPath);
        $branch  = escapeshellarg($app['branch'] ?? 'main');
        $ref     = escapeshellarg('origin/' . ($app['branch'] ?? 'main'));
        $repo    = $app['repo'] ?? null;

        if ($repo !== null) {
            $repoSafe    = escapeshellarg($repo);
            $ensureRepo  = "test -d {$rawPath}/.git || git -C {$path} init";
            $syncRemote  = "git -C {$path} remote set-url origin {$repoSafe} 2>/dev/null || git -C {$path} remote add origin {$repoSafe}";
            $pullCode    = "git -C {$path} fetch origin {$branch} && git -C {$path} reset --hard {$ref}";
            $gitSteps    = "{$ensureRepo} && {$syncRemote} && {$pullCode}";
        } else {
            $gitSteps = "git -C {$path} fetch origin {$branch} && git -C {$path} reset --hard {$ref}";
        }

        $composer = "composer -d {$path} install --no-dev --optimize-autoloader";

        return "{$gitSteps} && {$composer}";
    }

    private function buildActivateScript(array $env): string
    {
        $app        = $env['app'] ?? [];
        $rawPath    = $app['path'];
        $phpVersion = $env['php'] ?? '8.3';
        $hooks      = $app['hooks'] ?? [];

        $storagePath = escapeshellarg($rawPath . '/storage');
        $consolePath = escapeshellarg($rawPath . '/console');
        $appPath     = escapeshellarg($rawPath);

        $commands = [
            "find {$storagePath} -type d -exec chmod 2775 {} \\; && find {$storagePath} -type d -exec chgrp www-data {} \\;",
            "php {$consolePath} migrate:up --force",
        ];

        foreach ($hooks as $hook) {
            $commands[] = "cd {$appPath} && {$hook}";
        }

        $commands[] = "sudo systemctl reload php{$phpVersion}-fpm";

        return implode(' && ', $commands);
    }

    private function buildRollbackScript(array $env, int $steps): string
    {
        $path = escapeshellarg($env['app']['path']);

        $commands = [
            "cd {$path}",
            "echo 'Recent commits:'",
            'git log --oneline -5',
            "git reset --hard HEAD~{$steps}",
            "composer -d {$path} install --no-dev --optimize-autoloader",
            "echo ''",
            "echo 'Rolled back. Current commit:'",
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
        $key  = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');

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
     * Build SCP command as an array to copy local .env file to remote server.
     */
    private function buildScpCommand(array $env, string $localPath): array
    {
        $user = $env['user'];
        $host = $env['host'];
        $key        = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');
        $remotePath = $env['app']['path'] . '/.env';

        return [
            'scp',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            $localPath,
            "{$user}@{$host}:{$remotePath}",
        ];
    }

}
