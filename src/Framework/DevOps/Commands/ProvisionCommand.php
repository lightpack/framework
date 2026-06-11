<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\Provisioner;

/**
 * Provision a fresh Ubuntu server for Lightpack deployment.
 *
 * WARNING: This command requires ROOT SSH access to the server.
 * It is a ONE-TIME operation. After provisioning, use app:deploy
 * with the deploy user for daily deployments.
 *
 * Usage:
 *   php console server:provision              Provision default environment
 *   php console server:provision staging    Provision specific environment
 *   php console server:provision --php=8.4  Override PHP version
 */
class ProvisionCommand extends Command
{
    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);

        if (!isset($config['environments'][$env])) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $envConfig = $config['environments'][$env];

        // Show warnings and require confirmation
        if (!$this->confirmProvision($env, $envConfig)) {
            return self::FAILURE;
        }

        // Merge CLI overrides into config
        $this->applyOverrides($envConfig);
        $config['environments'][$env] = $envConfig;

        $provisioner = new Provisioner($config);

        // Run provisioning
        $this->output->newline();
        $this->output->info("Provisioning {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        try {
            $result = $provisioner->provision($env);
        } catch (\RuntimeException $e) {
            $this->output->error($e->getMessage());
            $this->output->newline();
            return self::FAILURE;
        }

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Provisioning failed (exit code: {$result['exit_code']}).");
            $this->output->newline();
            $this->output->line('Check the output above for error details.');
            return self::FAILURE;
        }

        $this->output->success("Server provisioned successfully!");
        $this->output->newline();

        // Fetch and save credentials
        $this->saveCredentials($provisioner, $env);

        $this->output->newline();
        $this->printNextSteps($env, $envConfig);

        return self::SUCCESS;
    }

    private function loadConfig(): ?array
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Create config/deploy.php with your server settings:');
            $this->output->newline();
            $this->printConfigExample();
            $this->output->newline();
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

    private function applyOverrides(array &$envConfig): void
    {
        $overrides = [
            'php_version' => $this->args->get('php'),
            'timezone'    => $this->args->get('timezone'),
            'database'    => $this->args->get('db'),
            'web_server'  => $this->args->get('web'),
        ];

        foreach ($overrides as $key => $value) {
            if ($value !== null) {
                $envConfig[$key] = $value;
            }
        }
    }

    private function confirmProvision(string $env, array $envConfig): bool
    {
        $this->output->newline();
        $this->output->warning('SECURITY WARNING');
        $this->output->newline();
        $this->output->line('This command requires ROOT SSH access to the server.');
        $this->output->line('It will install system packages, create users, and modify system settings.');
        $this->output->line('This is a ONE-TIME operation for fresh servers.');
        $this->output->newline();

        $this->output->info('Server:');
        $this->output->line("  Environment: {$env}");
        $this->output->line("  Host:        {$envConfig['host']}");
        $this->output->line("  PHP:         " . ($envConfig['php_version'] ?? '8.3'));
        $this->output->line("  Database:    " . ($envConfig['database'] ?? 'mysql'));
        $this->output->newline();

        if (!file_exists($this->resolveKeyPath($envConfig['key'] ?? '~/.ssh/id_rsa'))) {
            $this->output->error('SSH key not found: ' . ($envConfig['key'] ?? '~/.ssh/id_rsa'));
            $this->output->newline();
            return false;
        }

        $this->output->warning('Do you want to continue? (yes/no)');
        $this->output->newline();

        $response = trim(fgets(STDIN));

        return strtolower($response) === 'yes';
    }

    private function saveCredentials(Provisioner $provisioner, string $env): void
    {
        $this->output->info('Fetching credentials from server...');
        $this->output->newline();

        $credentialsDir = DIR_ROOT . '/deploy/credentials';

        if (!is_dir($credentialsDir)) {
            mkdir($credentialsDir, 0700, true);
        }

        $localPath = $credentialsDir . '/' . $env . '.txt';

        try {
            $result = $provisioner->fetchCredentials($env, $localPath);
        } catch (\RuntimeException $e) {
            $this->output->warning('Could not fetch credentials: ' . $e->getMessage());
            return;
        }

        if ($result['success'] && file_exists($localPath)) {
            chmod($localPath, 0600);
            $this->output->success("Credentials saved to: deploy/credentials/{$env}.txt");
        } else {
            $this->output->warning('Could not save credentials locally.');
            $this->output->line('They remain on the server at /root/.lightpack-credentials-final');
        }
    }

    private function printEnvironmentError(array $config, string $env): void
    {
        $this->output->error("Environment '{$env}' not found in config/deploy.php.");
        $this->output->newline();
        $this->output->line('Available environments:');

        $provisioner = new Provisioner($config);
        foreach ($provisioner->getEnvironments() as $name) {
            $this->output->line("  - {$name}");
        }

        $this->output->newline();
    }

    private function printNextSteps(string $env, array $envConfig): void
    {
        $this->output->info('Next steps:');
        $this->output->newline();
        $this->output->line("  1. Review credentials: deploy/credentials/{$env}.txt");
        $this->output->line('  2. Add the GitHub deploy key to your repository');
        $this->output->line("  3. Deploy your app:  php console app:deploy {$env}");
        $this->output->newline();
        $this->output->info('Security notes:');
        $this->output->line('  - Root SSH login is now DISABLED');
        $this->output->line('  - Use the deploy user for all future operations');
        $this->output->line('  - Deploy user sudo is restricted to service reloads only');
        $this->output->newline();
    }

    private function printConfigExample(): void
    {
        $example = <<<'PHP'
<?php

return [
    'default' => 'production',

    'environments' => [
        'production' => [
            'host'    => '1.2.3.4',
            'user'    => 'deploy',
            'key'     => '~/.ssh/id_rsa',
            'path'    => '/var/www/myapp',
            'branch'  => 'main',
            'timeout' => 300,

            // Provisioning options (optional):
            // 'php_version' => '8.3',
            // 'timezone'    => 'UTC',
            // 'database'    => 'mysql',   // mysql | none
            // 'web_server'  => 'nginx',   // nginx only for now
        ],
    ],
];
PHP;

        echo $example . PHP_EOL;
    }

    private function resolveKeyPath(string $key): string
    {
        if (strpos($key, '~') === 0) {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? '';
            return str_replace('~', $home, $key);
        }

        return $key;
    }
}
