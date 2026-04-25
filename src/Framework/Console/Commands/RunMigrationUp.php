<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\Command;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationUp extends Command
{
    public function run()
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            $this->output->error("Running migrations require ./.env which is missing.");
            $this->output->newline();
            return self::FAILURE;
        }

        if ('mysql' !== Env::get('DB_DRIVER')) {
            $this->output->error("Migrations are supported only for MySQL/MariaDB.");
            $this->output->newline();
            return self::FAILURE;
        }

        $force = $this->args->has('force');

        $confirm = $this->promptConfirmation($force);

        if(false === $confirm) {
            $this->output->newline();
            $this->output->success("✓ Migration cancelled.");
            $this->output->newline();
            return self::SUCCESS;
        }

        // Ensure database exists before running migrations
        $this->ensureDatabaseExists();

        $migrator = new Migrator($this->getConnection());
        
        $migrations = $migrator->run(DIR_ROOT . '/database/migrations');
        
        $this->output->newline();

        if(empty($migrations)) {
            $this->output->success("✓ Migrations already up-to-date.");
            $this->output->newline();
        } else {
            $this->output->line("Migrations:");

            foreach ($migrations as $migration) {
                $this->output->success("✓ {$migration}");
            }

            $this->output->newline();
        }
        
        return self::SUCCESS;
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

    private function promptConfirmation(bool $force = false): bool
    {
        // If --force flag is provided, skip confirmation
        if ($force) {
            return true;
        }

        if ('production' === strtolower(get_env('APP_ENV'))) {
            $this->output->newline();
            return $this->prompt->confirm('[Production] Are you sure you want to migrate?', false);
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
        $this->output->newline();
        $this->output->line("⚠ WARNING: The database '{$database}' does not exist on the 'mysql' connection.");
        $this->output->newline();
        
        if (!$this->prompt->confirm("Would you like to create it?", false)) {
            $this->output->newline();
            $this->output->success("✓ Operation cancelled. No database was created.");
            $this->output->newline();
            exit(0);
        }
        
        $this->createDatabase($database);
        
        $this->output->newline();
        $this->output->success("✓ Database '{$database}' created successfully.");
        $this->output->newline();
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
            $this->output->newline();
            $this->output->error("✗ Failed to create database: " . $e->getMessage());
            $this->output->newline();
            exit(1);
        }
    }
}
