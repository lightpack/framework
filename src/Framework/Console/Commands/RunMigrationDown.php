<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\CommandInterface;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationDown implements CommandInterface
{
    public function run(array $arguments = [])
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            fputs(STDOUT, "Running migrations require ./.env which is missing.\n\n");
            exit;
        }

        $driver = Env::get('DB_DRIVER');

        if ('mysql' !== $driver) {
            fputs(STDOUT, "Migrations are supported only for MySQL/MariaDB.\n\n");
            exit;
        }

        $migrator = new Migrator($this->getConnection());
        $steps = $this->getStepsArgument($arguments);
        $force = in_array('--force', $arguments);
        $confirm = $this->promptConfirmation($steps, $force);

        if ($confirm) {
            if('all' === $steps) {
                $migrations = $migrator->rollbackAll(DIR_ROOT . '/database/migrations');
            } else {
                $migrations = $migrator->rollback(DIR_ROOT . '/database/migrations', $steps);
            }

            fputs(STDOUT, "\n");

            if (empty($migrations)) {
                fputs(STDOUT, "✓ No migrations to rollback.\n");
            } else {
                fputs(STDOUT, "Rolled back migrations:\n");
                foreach ($migrations as $migration) {
                    fputs(STDOUT, "✓ {$migration}\n");
                }
                fputs(STDOUT, "\n");
            }
        }
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
                fputs(STDOUT, "Invalid database driver found in ./.env\n\n");
                exit;
        }
    }

    private function getStepsArgument(array $arguments)
    {
        $steps = $arguments[0] ?? null;

        if (null === $steps) {
            return null;
        }

        if ('--all' === $steps) {
            return 'all';
        }

        $steps = explode('=', $steps);

        $steps = $steps[1] ?? null;

        return $steps;
    }

    private function promptConfirmation(null|string|int $steps = null, bool $force = false): bool
    {
        // If --force flag is provided, skip confirmation
        if ($force) {
            return true;
        }

        fputs(STDOUT, "\n");

        if ('all' === $steps) {
            fputs(STDOUT, "Are you sure you want to rollback all the migrations? [y/N]: ");
        } else if (null === $steps || 1 === $steps) {
            fputs(STDOUT, "Are you sure you want to rollback last batch of migrations? [y/N]: ");
        } else {
            fputs(STDOUT, "Are you sure you want to rollback last {$steps} batch of migrations? [y/N]: ");
        }

        return strtolower(trim(fgets(STDIN))) === 'y';
    }
}
