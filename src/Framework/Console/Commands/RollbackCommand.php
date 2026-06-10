<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\Deployer;

/**
 * Rollback the application to a previous commit on a remote server.
 *
 * Usage:
 *   php lightpack deploy:rollback              Rollback default env by 1 commit
 *   php lightpack deploy:rollback --env=staging Rollback staging by 1 commit
 *   php lightpack deploy:rollback --steps=2    Rollback by 2 commits
 */
class RollbackCommand extends Command
{
    public function run()
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Create config/deploy.php with your server settings.');
            $this->output->newline();
            return self::FAILURE;
        }

        $config = require $configPath;
        $defaultEnv = $config['default'] ?? 'production';
        $env = $this->args->get('env', $defaultEnv);
        $steps = (int) $this->args->get('steps', 1);

        if ($steps < 1) {
            $this->output->error('Rollback steps must be at least 1.');
            return self::FAILURE;
        }

        if (!isset($config['environments'][$env])) {
            $this->output->error("Environment '{$env}' not found in config/deploy.php.");
            $this->output->newline();
            $this->output->line('Available environments:');

            $deployer = new Deployer($config);
            foreach ($deployer->getEnvironments() as $name) {
                $this->output->line("  - {$name}");
            }

            $this->output->newline();
            return self::FAILURE;
        }

        $envConfig = $config['environments'][$env];

        $this->output->warning("Rolling back {$env} ({$envConfig['host']}) by {$steps} commit(s)...");
        $this->output->newline();

        $deployer = new Deployer($config);

        try {
            $result = $deployer->rollback($env, $steps);
        } catch (\RuntimeException $e) {
            $this->output->error($e->getMessage());
            $this->output->newline();
            return self::FAILURE;
        }

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Rolled back {$env} successfully.");
            $this->output->newline();
            $this->output->warning('Note: Check migrations manually if the rollback involves database changes.');
            return self::SUCCESS;
        }

        $this->output->error("Rollback failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }
}
