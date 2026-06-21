<?php

namespace Lightpack\Deploy;

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

    /**
     * @param array $config The ['deploy'] sub-array from config/deploy.php.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Provision the specified server environment.
     *
     * @param string $environment  Environment key (e.g. 'production').
     * @param array  $params       Interactive params: init_user, php_version,
     *                             db_name, db_user, timezone.
     * @return array{success: bool, exit_code: int, output: string}
     */
    public function provision(string $environment, array $params): array
    {
        $env = $this->config[$environment] ?? null;

        if ($env === null) {
            throw new \RuntimeException("Environment '{$environment}' not found in config/deploy.php");
        }

        $host         = $env['host'];
        $initUser     = $params['init_user'];
        $key          = $this->resolveKeyPath($env['key']);
        $provisionOptions = $this->buildProvisionOptions($env, $params);

        // Step 1: Add host to known_hosts to avoid interactive prompt
        $this->addToKnownHosts($host);

        // Step 2: Generate and copy provisioning script to server
        $scriptPath       = $this->generateScript($provisionOptions);
        $remoteScriptPath = '/tmp/lightpack-provision.sh';

        try {
            $scpResult = $this->copyScriptToServer($host, $initUser, $key, $scriptPath, $remoteScriptPath);

            if (!$scpResult['success']) {
                return $scpResult;
            }

            // Step 3: Execute provisioning script with sudo.
            // The script requires root for apt, useradd, etc.
            // It self-deletes at its end via `rm -f "$0"`.
            return $this->executeScriptOnServer($host, $initUser, $key, $remoteScriptPath);
        } finally {
            // Always cleanup local temp script
            @unlink($scriptPath);
        }
    }

    /**
     * Get configured environment names.
     */
    public function getEnvironments(): array
    {
        return array_keys($this->config);
    }

    /**
     * Generate the provisioning script with environment variable exports prepended.
     */
    private function generateScript(array $options): string
    {
        $template = file_get_contents(__DIR__ . '/scripts/provision.sh');

        if ($template === false) {
            throw new \RuntimeException('Provisioning script template not found');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'lp_provision_');

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

    /**
     * Build the environment variable map passed to provision.sh.
     *
     * git_host is derived from the repo URL so it never needs to be in config.
     */
    private function buildProvisionOptions(array $env, array $params): array
    {
        $gitHost = 'github.com';
        if (!empty($env['repo']) && preg_match('/^git@([^:]+):/', $env['repo'], $m)) {
            $gitHost = $m[1];
        }

        return [
            'SERVER_NAME'  => $params['name'] ?? 'lightpack',
            'DEPLOY_USER'  => 'deploy',
            'PHP_VERSION'  => $params['php_version'],
            'TIMEZONE'     => $params['timezone'],
            'DB_TYPE'      => 'mysql',
            'WEB_SERVER'   => 'nginx',
            'MYSQL_DB'     => $params['db_name'],
            'MYSQL_USER'   => $params['db_user'],
            'GIT_HOST'     => $gitHost,
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
            "DEBIAN_FRONTEND=noninteractive sudo bash {$remotePath} 2>&1",
        ];

        // Provisioning can take 10-15 minutes
        return $this->execute($sshCommand, 1200);
    }

}
