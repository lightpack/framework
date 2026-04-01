<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\BaseCommand;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationDown extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            $this->output->error("Running migrations require ./.env which is missing.");
            $this->output->newline();
            return 1;
        }

        $driver = Env::get('DB_DRIVER');

        if ('mysql' !== $driver) {
            $this->output->error("Migrations are supported only for MySQL/MariaDB.");
            $this->output->newline();
            return 1;
        }

        $migrator = new Migrator($this->getConnection());
        $steps = $this->getStepsArgument();
        $force = $this->args->has('force');
        $confirm = $this->promptConfirmation($steps, $force);

        if ($confirm) {
            if('all' === $steps) {
                $migrations = $migrator->rollbackAll(DIR_ROOT . '/database/migrations');
            } else {
                $migrations = $migrator->rollback(DIR_ROOT . '/database/migrations', $steps);
            }

            $this->output->newline();

            if (empty($migrations)) {
                $this->output->success("✓ No migrations to rollback.");
            } else {
                $this->output->line("Rolled back migrations:");
                foreach ($migrations as $migration) {
                    $this->output->success("✓ {$migration}");
                }
                $this->output->newline();
            }
        }
        
        return 0;
    }

    private function getConnection()
    {
        switch (Env::get('DB_DRIVER')) {
            case 'mysql':
                return new Mysql([
                    'host'      => Env::get('DB_HOST'),
                    'port'      => Env::get('DB_PORT'),
                    'username'  => Env::get('DB_USER'),
                    'password'  => Env::get('DB_PSWD'),
                    'database'  => Env::get('DB_NAME'),
                    'options'   => [],
                ]);
            default:
                $this->output->error("Invalid database driver found in ./.env");
                $this->output->newline();
                exit(1);
        }
    }

    private function getStepsArgument()
    {
        if ($this->args->has('all')) {
            return 'all';
        }
        
        $steps = $this->args->get('steps');
        
        if ($steps) {
            return (int) $steps;
        }
        
        // Check first positional argument
        $firstArg = $this->args->argument(0);
        if ($firstArg && is_numeric($firstArg)) {
            return (int) $firstArg;
        }
        
        return null;
    }

    private function promptConfirmation(null|string|int $steps = null, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $this->output->newline();

        if ('all' === $steps) {
            return $this->prompt->confirm("Are you sure you want to rollback all the migrations?", false);
        } else if (null === $steps || 1 === $steps) {
            return $this->prompt->confirm("Are you sure you want to rollback last batch of migrations?", false);
        } else {
            return $this->prompt->confirm("Are you sure you want to rollback last {$steps} batch of migrations?", false);
        }
    }
}
