<?php

namespace Lightpack\Database\Migrations;

use Lightpack\Database\Pdo;
use Lightpack\File\File;

class Migrator
{
    /**
     * @var \Lightpack\Database\Pdo
     */
    private $connection;

    public function __construct(Pdo $connection)
    {
        $this->connection = $connection;
        $this->createMigrationsTable();
    }

    public function run(string $path)
    {
        $migrationFiles = (new File)->traverse($path);

        $allMigrations = array_keys($migrationFiles);
        $executedMigrations = $this->getExecutedMigrations();
        $migrations = array_diff($allMigrations, $executedMigrations);

        ksort($migrations);

        foreach ($migrations as $migration) {
            $migrationFile = $migrationFiles[$migration];
            $migrationFilepath = $migrationFile->getPathname();

            // Execute migration
            $sql = file_get_contents($migrationFilepath);

            if(trim($sql)) {
                $this->connection->query($sql);  
            }

            // Record migration
            $sql = "INSERT INTO migrations (migration) VALUES ('{$migration}');";
            $this->connection->query($sql);
        }
    }

    public function rollback($path, int $steps = null)
    {
        $migrationFiles = (new File)->traverse($path);

        $migrations = array_keys($migrationFiles);

        // Reverse sort migrations
        krsort($migrations);

        if($steps) {
            $migrations = array_slice($migrations, 0, $steps);
        }

        foreach ($migrations as $migration) {
            $migrationFile = $migrationFiles[$migration];
            $migrationFilepath = $migrationFile->getPathname();

            // Execute migration
            $sql = file_get_contents($migrationFilepath);

            if(trim($sql)) {
                $this->connection->query($sql);  
            }

            // Delete migration
            $sql = "DELETE FROM migrations WHERE migration = '{$migration}'";
            $this->connection->query($sql);
        }
    }

    private function getExecutedMigrations()
    {
        $migrations = [];
        $rows = $this->connection->query('SELECT migration FROM migrations')->fetchAll();
        
        foreach($rows as $row) {
            $migrations[] = $row['migration'];
        }

        return $migrations;
    }

    private function createMigrationsTable()
    {
        $this->connection->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id int NOT NULL AUTO_INCREMENT,
                migration VARCHAR(255),
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ); 
        ");
    }
}
