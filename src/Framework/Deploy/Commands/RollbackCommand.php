<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\Deployer;

/**
 * Rollback the application to a previous commit on a remote server.
 *
 * Usage:
 *   php console app:rollback              Rollback default environment by 1 commit
 *   php console app:rollback production   Rollback a specific environment
 *   php console app:rollback staging --steps=2
 */
class RollbackCommand extends Command
{
    use HasDeployConfig;

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

        $steps = $this->args->get('steps');

        if ($steps === null) {
            $this->output->newline();
            $this->output->info("→ Rolling back on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $steps = $this->askWithDefault('Commits to rollback', '1');
        }

        $steps = max(1, (int) $steps);

        $this->output->warning("→ Rolling back {$env} ({$envConfig['host']}) by {$steps} commit(s) \u2500\u2500\u2500 this cannot be undone ...");
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
            $this->output->success("✓ Rolled back {$env} successfully.");
            $this->output->newline();
            $this->output->warning('Note: Rollback does not revert database migrations. Handle those manually if needed.');
            return self::SUCCESS;
        }

        $this->output->error("Rollback failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

}
