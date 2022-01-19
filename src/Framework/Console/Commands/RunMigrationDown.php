<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\ICommand;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Adapters\Sqlite;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationDown implements ICommand
{
    public function run(array $arguments = [])
    {
        if (!file_exists(DIR_ROOT . '/env.php')) {
            fputs(STDOUT, "Running migrations require ./env.php which is missing.\n\n");
            exit;
        }

        $config = require DIR_ROOT . '/env.php';

        if ('mysql' !== $config['DB_DRIVER']) {
            fputs(STDOUT, "Migrations are supported only for MySQL/MariaDB.\n\n");
            exit;
        }

        $migrator = new Migrator($this->getConnection($config));
        $steps = $this->getStepsArgument($arguments);
        $confirm = $this->promptConfirmation($steps);

        if ($confirm) {
            $migrations = $migrator->rollback(DIR_ROOT . '/database/migrations/down', $steps);

            if(empty($migrations)) {
                fputs(STDOUT, "No migrations to rollback.\n\n");
            } else {
                fputs(STDOUT, "Rolled back migrations:\n");
                foreach ($migrations as $migration) {
                    fputs(STDOUT, "  {$migration}\n");
                }
                fputs(STDOUT, "\n");
            }
        }
    }

    private function getConnection(array $config)
    {
        switch ($config['DB_DRIVER']) {
            case 'mysql':
                return new Mysql([
                    'host'      => $config['DB_HOST'],
                    'port'      => $config['DB_PORT'],
                    'username'  => $config['DB_USER'],
                    'password'  => $config['DB_PSWD'],
                    'database'  => $config['DB_NAME'],
                    'options'   => [],
                ]);
            default:
                fputs(STDOUT, "Invalid database driver found in ./env.php\n\n");
                exit;
        }
    }

    private function getStepsArgument(array $arguments)
    {
        $steps = $arguments[0] ?? null;

        if (null === $steps) {
            return null;
        }

        $steps = explode('=', $steps);

        $steps = $steps[1] ?? null;

        return $steps;
    }

    private function promptConfirmation($steps): bool
    {
        if (null === $steps || 1 === $steps) {
            fputs(STDOUT, "Are you sure you want to rollback last batch of migrations? [y/N] ");
        } else {
            fputs(STDOUT, "Are you sure you want to rollback last {$steps} batch of migrations? [y/N] ");
        }

        return strtolower(trim(fgets(STDIN))) === 'y';
    }
}
