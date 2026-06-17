<?php

namespace Lightpack\DevOps;

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
    use RunsProcess;

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
        $provisionUser = $env['provision']['user'] ?? 'root';
        $rootKey = $this->resolveKeyPath($env['key'] ?? '~/.ssh/id_rsa');
        $provisionOptions = $this->buildProvisionOptions($env);

        // Step 1: Add host to known_hosts to avoid interactive prompt
        $this->addToKnownHosts($host);

        // Step 2: Generate and copy provisioning script to server
        $scriptPath = $this->generateScript($provisionOptions);
        $remoteScriptPath = '/root/lightpack-provision.sh';

        try {
            $scpResult = $this->copyScriptToServer($host, $provisionUser, $rootKey, $scriptPath, $remoteScriptPath);

            if (!$scpResult['success']) {
                return $scpResult;
            }

            // Step 3: Execute provisioning script
            // Note: provision.sh self-deletes at its end via `rm -f "$0"`.
            // PHP cannot clean it up because root SSH is disabled after provisioning
            // and the deploy user has no access to /root/.
            return $this->executeScriptOnServer($host, $provisionUser, $rootKey, $remoteScriptPath);
        } finally {
            // Step 5: Always cleanup local temp script
            @unlink($scriptPath);
        }
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
            $escaped = str_replace("'", "'\\''" , $value);
            $exports .= "export {$key}='{$escaped}'\n";
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
        $provision = $env['provision'] ?? [];

        return [
            'SERVER_NAME' => $provision['name'] ?? 'lightpack',
            'DEPLOY_USER' => $env['user'] ?? 'deploy',
            'PHP_VERSION' => $env['php'] ?? '8.3',
            'TIMEZONE'    => $provision['timezone'] ?? 'UTC',
            'DB_TYPE'     => $provision['database'] ?? 'mysql',
            'WEB_SERVER'  => 'nginx',
            'MYSQL_DB'    => $provision['db_name'] ?? 'lightpack',
            'MYSQL_USER'  => $provision['db_user'] ?? 'lightpack',
            'GIT_HOST'    => $provision['git_host'] ?? 'github.com',
        ];
    }

    private function addToKnownHosts(string $host): void
    {
        $home   = $_SERVER['HOME'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? '';
        $sshDir = $home . '/.ssh';

        if (!is_dir($sshDir)) {
            mkdir($sshDir, 0700, true);
        }

        $result = $this->execute(['ssh-keyscan', '-H', $host], 10);

        if ($result['output'] !== '') {
            file_put_contents($sshDir . '/known_hosts', $result['output'], FILE_APPEND | LOCK_EX);
        }
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

    private function executeScriptOnServer(string $host, string $user, string $key, string $remotePath): array
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

}
