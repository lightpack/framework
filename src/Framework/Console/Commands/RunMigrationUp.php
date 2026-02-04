<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\CommandInterface;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationUp implements CommandInterface
{
    public function run(array $arguments = [])
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            fputs(STDOUT, "Running migrations require ./.env which is missing.\n\n");
            exit;
        }

        if ('mysql' !== Env::get('DB_DRIVER')) {
            fputs(STDOUT, "Migrations are supported only for MySQL/MariaDB.\n\n");
            exit;
        }

        // Check for --force flag
        $force = in_array('--force', $arguments);

        $confirm = $this->promptConfirmation($force);

        if(false === $confirm) {
            fputs(STDOUT, "\n✓ Migration cancelled.\n");
            exit;
        }

        // Ensure database exists before running migrations
        $this->ensureDatabaseExists();

        $migrator = new Migrator($this->getConnection());
        
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

    private function promptConfirmation(bool $force = false): bool
    {
        // If --force flag is provided, skip confirmation
        if ($force) {
            return true;
        }

        if ('production' === strtolower(get_env('APP_ENV'))) {
            fputs(STDOUT, "\n[Production] Are you sure you want to migrate? [y/N]: ");
            return strtolower(trim(fgets(STDIN))) === 'y';
        } 

        return true;
    }

    /**
     * Ensure the database exists, create it if missing.
     *
     * @return void
     */
    private function ensureDatabaseExists(): void
    {
        $database = Env::get('DB_NAME');
        
        try {
            // Try to connect with the database specified
            $this->getConnection();
        } catch (\Exception $e) {
            // Check if the error is due to missing database
            if ($this->isDatabaseMissingError($e)) {
                $this->handleMissingDatabase($database);
            } else {
                // Re-throw if it's a different error
                throw $e;
            }
        }
    }

    /**
     * Check if the exception is due to a missing database.
     *
     * @param \Exception $e
     * @return bool
     */
    private function isDatabaseMissingError(\Exception $e): bool
    {
        $message = $e->getMessage();
        
        // MySQL error 1049: Unknown database
        return str_contains($message, 'Unknown database') || 
               str_contains($message, "SQLSTATE[HY000] [1049]");
    }

    /**
     * Handle missing database by prompting to create it.
     *
     * @param string $database
     * @return void
     */
    private function handleMissingDatabase(string $database): void
    {
        fputs(STDOUT, "\n");
        fputs(STDOUT, "⚠ WARNING: The database '{$database}' does not exist on the 'mysql' connection.\n\n");
        fputs(STDOUT, "Would you like to create it? [y/N]: ");
        
        $response = strtolower(trim(fgets(STDIN)));
        
        if ($response !== 'y') {
            fputs(STDOUT, "\n✓ Operation cancelled. No database was created.\n\n");
            exit;
        }
        
        $this->createDatabase($database);
        
        fputs(STDOUT, "\n✓ Database '{$database}' created successfully.\n\n");
    }

    /**
     * Create the database.
     *
     * @param string $database
     * @return void
     */
    private function createDatabase(string $database): void
    {
        try {
            // Connect without specifying a database
            $connection = new Mysql([
                'host'      => Env::get('DB_HOST'),
                'port'      => Env::get('DB_PORT'),
                'username'  => Env::get('DB_USER'),
                'password'  => Env::get('DB_PSWD'),
                'database'  => null, // No database specified
                'options'   => [],
            ]);
            
            // Create the database
            $connection->query("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Exception $e) {
            fputs(STDOUT, "\n✗ Failed to create database: " . $e->getMessage() . "\n\n");
            exit;
        }
    }
}
