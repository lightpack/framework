<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\ICommand;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Adapters\Sqlite;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationUp implements ICommand
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

        $confirm = $this->promptConfirmation();

        if(false === $confirm) {
            fputs(STDOUT, "\n✓ Migration cancelled.\n");
            exit;
        }

        $migrator = new Migrator($this->getConnection($config));

        $migrations = $migrator->run(DIR_ROOT . '/database/migrations');
        
        fputs(STDOUT, "\n");

        if(empty($migrations)) {
            fputs(STDOUT, "✓ Migrations already up-to-date.\n\n");
        } else {
            fputs(STDOUT, "Migrations:\n");

            foreach ($migrations as $migration) {
                fputs(STDOUT, "✓ {$migration}\n");
            }

            fputs(STDOUT, "\n");
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

    private function promptConfirmation(): bool
    {
        if ('production' === strtolower(get_env('APP_ENV'))) {
            fputs(STDOUT, "\n[Production] Are you sure you want to migrate? [y/N]: ");
            return strtolower(trim(fgets(STDIN))) === 'y';
        } 

        return true;
    }
}
