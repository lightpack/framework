<?php

namespace Lightpack\Deploy;

/**
 * Handles remote deployment via SSH.
 * Executes deploy commands on a remote server using system SSH.
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
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function deploy(string $environment): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $remoteScript = $this->buildRemoteScript($env);
        $sshCommand = $this->buildSshCommand($env, $remoteScript);

        return $this->execute($sshCommand);
    }

    /**
     * Get configured environment names.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->config['environments'] ?? []);
    }

    private function buildRemoteScript(array $env): string
    {
        $path = $env['path'];
        $branch = $env['branch'] ?? 'main';

        $commands = $env['commands'] ?? [
            'cd {path}',
            'git fetch origin {branch}',
            'git reset --hard origin/{branch}',
            'composer install --no-dev --optimize-autoloader',
            'php lightpack migrate:up',
            'php lightpack cache:clear',
        ];

        $script = implode(' && ', $commands);

        return str_replace(
            ['{path}', '{branch}'],
            [$path, $branch],
            $script
        );
    }

    private function buildSshCommand(array $env, string $remoteScript): string
    {
        $user = $env['user'];
        $host = $env['host'];
        $key = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');

        return sprintf(
            'ssh -i %s -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 %s@%s %s',
            escapeshellarg($key),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($remoteScript)
        );
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
     * Execute a shell command and stream output in real-time.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    private function execute(string $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, null, null);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start SSH process. Is ssh installed?');
        }

        fclose($pipes[0]);

        $output = '';

        // Read stdout and stderr in real-time
        while (true) {
            $status = proc_get_status($process);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            if ($stdout !== false && $stdout !== '') {
                $output .= $stdout;
                echo $stdout;
                flush();
            }

            if ($stderr !== false && $stderr !== '') {
                $output .= $stderr;
                echo $stderr;
                flush();
            }

            if (!$status['running']) {
                break;
            }

            usleep(100000); // 100ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }
}
