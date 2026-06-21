<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;
use Lightpack\Deploy\Provisioner;

/**
 * Provision a fresh Ubuntu server for Lightpack deployment.
 *
 * Reads host and repo from config/deploy.php, then gathers remaining
 * provisioning parameters interactively with smart defaults.
 *
 * This is a ONE-TIME operation. After provisioning, root SSH is disabled
 * and all future operations use the 'deploy' user.
 *
 * Usage:
 *   php console server:provision              Provision default (production) environment
 *   php console server:provision staging      Provision a specific environment
 */
class ProvisionCommand extends Command
{
    use HasDeployConfigTrait;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $keyPath = $this->resolveKeyPath($envConfig['key']);

        if (!file_exists($keyPath)) {
            $this->output->error("SSH key not found: {$envConfig['key']}");
            $this->output->line('Set the correct path in config/deploy.php under the "key" entry.');
            return self::FAILURE;
        }

        // Gather provisioning parameters interactively
        $params = $this->gatherParams($env, $envConfig);

        // Show confirmation and require explicit approval
        if (!$this->confirmProvision($env, $envConfig, $params)) {
            return self::FAILURE;
        }

        $provisioner = new Provisioner($config);

        $this->output->newline();
        $this->output->info("Provisioning {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        try {
            $result = $provisioner->provision($env, $params);
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

        $this->output->newline();
        $this->output->success('Server provisioned successfully!');
        $this->output->newline();
        $this->printNextSteps($env);

        return self::SUCCESS;
    }

    /**
     * Gather provisioning parameters interactively, using smart defaults.
     *
     * Supports non-interactive mode via CLI flags:
     *   --init-user=root --php=8.3 --db-name=myapp --db-user=myapp --timezone=UTC
     */
    private function gatherParams(string $env, array $envConfig): array
    {
        $defaultDbName = preg_replace('/[^a-zA-Z0-9_]/', '_', basename($envConfig['path']));
        $defaultDbUser = $defaultDbName;

        $initUser   = $this->args->get('init-user');
        $phpVersion = $this->args->get('php');
        $dbName     = $this->args->get('db-name');
        $dbUser     = $this->args->get('db-user');
        $timezone   = $this->args->get('timezone');

        if ($initUser === null || $phpVersion === null || $dbName === null || $dbUser === null || $timezone === null) {
            $this->output->newline();
            $this->output->info("→ Provisioning {$env} ({$envConfig['host']})");
            $this->output->newline();

            $initUser   = $initUser   ?? $this->askWithDefault('SSH user for root access', 'root');
            $phpVersion = $phpVersion ?? $this->askWithDefault('PHP version to install', '8.3');
            $dbName     = $dbName     ?? $this->askWithDefault('Database name', $defaultDbName);
            $dbUser     = $dbUser     ?? $this->askWithDefault('Database user', $defaultDbUser);
            $timezone   = $timezone   ?? $this->askWithDefault('Server timezone', 'UTC');
        }

        return [
            'init_user'   => $initUser,
            'php_version' => $phpVersion,
            'db_name'     => $dbName,
            'db_user'     => $dbUser,
            'timezone'    => $timezone,
            'name'        => $env,
        ];
    }

    private function confirmProvision(string $env, array $envConfig, array $params): bool
    {
        $this->output->newline();
        $this->output->line('  ─────────────────────────────────────────────');
        $this->output->line("  Environment:  {$env}");
        $this->output->line("  Host:         {$envConfig['host']}");
        $this->output->line("  SSH key:      {$envConfig['key']}");
        $this->output->line("  Init user:    {$params['init_user']}");
        $this->output->line("  PHP:          {$params['php_version']}");
        $this->output->line("  Database:     {$params['db_name']} / user: {$params['db_user']}");
        $this->output->line("  Timezone:     {$params['timezone']}");
        $this->output->line('  ─────────────────────────────────────────────');
        $this->output->newline();
        $this->output->warning('Root SSH will be DISABLED after provisioning.');
        $this->output->newline();

        return $this->confirm('Continue?', false);
    }

    private function printNextSteps(string $env): void
    {
        $this->output->info('Next steps:');
        $this->output->newline();
        $this->output->line('  1. Copy the deploy SSH key shown above and add it to your Git repository');
        $this->output->line('       GitHub → Settings → Deploy keys → Add deploy key');
        $this->output->line("  2. Deploy:  php console app:deploy {$env}");
        $this->output->newline();
        $this->output->info('Security:');
        $this->output->line('  - Root SSH is now DISABLED on the server');
        $this->output->line('  - All future commands use the deploy user');
        $this->output->line('  - Deploy sudo is restricted to service reloads only');
        $this->output->newline();
    }
}
