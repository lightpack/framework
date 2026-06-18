<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\Deployer;

/**
 * Deploy the application to a remote server via SSH.
 *
 * Usage:
 *   php console app:deploy              Deploy to default environment
 *   php console app:deploy production   Deploy to a specific environment
 *   php console app:deploy staging      Deploy to staging environment
 */
class DeployCommand extends Command
{
    use HasDeployConfig;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);

        if (!isset($config[$env])) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $envConfig = $config[$env];
        $deployer = new Deployer($config);

        $this->output->info("Deploying to {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        return $this->deployCode($deployer, $env);
    }

    private function deployCode(Deployer $deployer, string $env): int
    {
        $this->output->line('Deploying code ...');
        $this->output->newline();

        $localEnvPath = DIR_ROOT . '/.env.' . $env;

        try {
            $result = $deployer->deploy($env, file_exists($localEnvPath) ? $localEnvPath : null);
        } catch (\RuntimeException $e) {
            $this->output->error($e->getMessage());
            $this->output->newline();
            return self::FAILURE;
        }

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Deployed successfully to {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Deploy failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

}
