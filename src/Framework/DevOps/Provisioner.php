<?php

namespace Lightpack\DevOps;

use Lightpack\Utils\Process;

/**
 * Handles remote server provisioning via SSH as root.
 *
 * Provisioning is a one-time operation that sets up a fresh Ubuntu server
 * for Lightpack application deployment. It requires root SSH access.
 *
 * Security note: The provisioning script creates a deploy user with
 * RESTRICTED passwordless sudo (service reloads only). The deploy user
 * CANNOT install packages or run arbitrary commands as root.
 */
class Provisioner
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Provision the specified server environment.
     *
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function provision(string $environment): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $host = $env['host'];
        $provisionUser = $env['provision_user'] ?? 'root';
        $rootKey = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');
        $provisionOptions = $this->buildProvisionOptions($env);

        // Step 1: Add host to known_hosts to avoid interactive prompt
        $this->addToKnownHosts($host);

        // Step 2: Generate and copy provisioning script to server
        $scriptPath = $this->generateScript($provisionOptions);
        $remoteScriptPath = '/root/lightpack-provision.sh';

        $scpResult = $this->copyScriptToServer($host, $provisionUser, $rootKey, $scriptPath, $remoteScriptPath);

        if (!$scpResult['success']) {
            unlink($scriptPath);
            return $scpResult;
        }

        // Step 3: Execute provisioning script
        $sshResult = $this->executeScriptOnServer($host, $provisionUser, $rootKey, $remoteScriptPath, $environment);

        // Step 4: Cleanup local temp script
        unlink($scriptPath);

        return $sshResult;
    }

    /**
     * Fetch credentials file from the remote server after provisioning.
     *
     * @return array{success: bool, output: string}
     */
    public function fetchCredentials(string $environment, string $localPath): array
    {
        $env = $this->config['environments'][$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $host = $env['host'];
        $deployUser = $env['user'] ?? 'deploy';
        $deployKey = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');

        // Credentials are at /tmp/lightpack-credentials (readable by all)
        // We fetch them as deploy user since root SSH is now disabled
        $scpCommand = [
            'scp',
            '-i', $deployKey,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "{$deployUser}@{$host}:/tmp/lightpack-credentials",
            $localPath,
        ];

        $result = $this->execute($scpCommand, 30);

        // Cleanup remote temp file regardless of success
        $this->execute([
            'ssh',
            '-i', $deployKey,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "{$deployUser}@{$host}",
            'rm -f /tmp/lightpack-credentials',
        ], 10);

        return [
            'success' => $result['success'],
            'output' => $result['output'],
        ];
    }

    /**
     * Get configured environment names.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->config['environments'] ?? []);
    }

    /**
     * Generate the provisioning script with substituted configuration values.
     */
    private function generateScript(array $options): string
    {
        $template = file_get_contents(__DIR__ . '/scripts/provision.sh');

        if ($template === false) {
            throw new \RuntimeException('Provisioning script template not found');
        }

        // Create a temporary file with substituted values
        $tmpFile = tempnam(sys_get_temp_dir(), 'lp_provision_');

        // Prepend environment variable exports so the script picks them up
        $exports = "#!/bin/bash\n\n";
        foreach ($options as $key => $value) {
            $escaped = str_replace('"', '\\"', $value);
            $exports .= "export {$key}=\"{$escaped}\"\n";
        }
        $exports .= "\n";

        // Remove the shebang from template and prepend our exports
        $script = preg_replace('/^#!\/bin\/bash\s*\n/', '', $template);
        $script = $exports . $script;

        file_put_contents($tmpFile, $script);
        chmod($tmpFile, 0700);

        return $tmpFile;
    }

    private function buildProvisionOptions(array $env): array
    {
        return [
            'SERVER_NAME' => $env['name'] ?? 'lightpack',
            'DEPLOY_USER' => $env['user'] ?? 'deploy',
            'PHP_VERSION' => $env['php_version'] ?? '8.3',
            'TIMEZONE'    => $env['timezone'] ?? 'UTC',
            'DB_TYPE'     => $env['database'] ?? 'mysql',
            'WEB_SERVER'  => $env['web_server'] ?? 'nginx',
            'MYSQL_DB'    => $env['db_name'] ?? 'lightpack',
            'MYSQL_USER'  => $env['db_user'] ?? 'lightpack',
        ];
    }

    private function addToKnownHosts(string $host): void
    {
        $keyscanCommand = sprintf(
            'ssh-keyscan -H %s >> ~/.ssh/known_hosts 2>/dev/null',
            escapeshellarg($host)
        );
        exec($keyscanCommand);
    }

    private function copyScriptToServer(string $host, string $user, string $key, string $localPath, string $remotePath): array
    {
        $scpCommand = [
            'scp',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            $localPath,
            "{$user}@{$host}:{$remotePath}",
        ];

        return $this->execute($scpCommand, 60);
    }

    private function executeScriptOnServer(string $host, string $user, string $key, string $remotePath, string $serverName): array
    {
        $sshCommand = [
            'ssh',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            "{$user}@{$host}",
            "DEBIAN_FRONTEND=noninteractive bash {$remotePath} 2>&1",
        ];

        // Provisioning can take 10-15 minutes
        return $this->execute($sshCommand, 1200);
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
     * Execute command using Process utility.
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
