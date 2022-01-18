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
        $migrationFiles = $this->findMigrationFiles($path);
        $allMigrations = array_keys($migrationFiles);
        $executedMigrations = $this->getExecutedMigrations();
        $migrationsToRun = array_diff($allMigrations, $executedMigrations);

        ksort($migrationsToRun);

        // Get next migration batch
        $nextBatch = $this->getLastBatch() + 1;

        foreach ($migrationsToRun as $migration) {
            $migrationFile = $migrationFiles[$migration];
            $migrationFilepath = $migrationFile->getPathname();

            // Execute migration
            $sql = file_get_contents($migrationFilepath);

            if (trim($sql)) {
                $this->connection->query($sql);
            }

            // Record migration
            $sql = "INSERT INTO migrations (migration, batch) VALUES ('{$migration}', {$nextBatch});";
            $this->connection->query($sql);
        }
    }

    /**
     * Rollback migrations.
     *
     * @param string $path Migration rollback directory.
     * @param integer|null $steps No. of batches to rollback.
     * @return array Array of rolled back migratins.
     */
    public function rollback($path, int $steps = null): array
    {
        $migrationFiles = $this->findMigrationFiles($path);

        $migrations = array_keys($migrationFiles);

        // Reverse sort migrations
        krsort($migrations);

        $steps = $steps ?? 1;
        $migratedFiles = [];

        for ($i = 0; $i < $steps; $i++) {

            // Get migrations for the last batch
            $lastBatchMigrations = $this->getLastBatchMigrations();

            if(empty($lastBatchMigrations)) {
                break;
            }

            foreach ($lastBatchMigrations as $migration) {
                $migrationFile = $migrationFiles[$migration];
                $migrationFilepath = $migrationFile->getPathname();

                // Execute migration
                $sql = file_get_contents($migrationFilepath);

                if (trim($sql)) {
                    $this->connection->query($sql);
                }

                // Delete migration
                $sql = "DELETE FROM migrations WHERE migration = '{$migration}'";
                $this->connection->query($sql);

                $migratedFiles[] = $migration;
            }
        }

        return $migratedFiles;
    }

    private function getExecutedMigrations()
    {
        $migrations = [];
        $rows = $this->connection->query('SELECT migration FROM migrations')->fetchAll();

        foreach ($rows as $row) {
            $migrations[] = $row['migration'];
        }

        return $migrations;
    }

    private function findMigrationFiles(string $path): array
    {
        $files = (new File)->traverse($path);

        foreach ($files as $index => $file) {
            if ($file->getExtension() !== 'sql') {
                unset($files[$index]);
            }
        }

        return $files;
    }

    private function createMigrationsTable()
    {
        $this->connection->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id int NOT NULL AUTO_INCREMENT,
                migration VARCHAR(255),
                batch int NOT NULL,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ); 
        ");
    }

    private function getLastBatch()
    {
        $sql = "SELECT MAX(batch) AS batch FROM migrations";
        $row = $this->connection->query($sql)->fetch();

        return $row['batch'] ?? 0;
    }

    private function getLastBatchMigrations()
    {
        $sql = "SELECT migration FROM migrations WHERE batch = {$this->getLastBatch()} ORDER BY id DESC";
        $rows = $this->connection->query($sql)->fetchAll();

        $migrations = [];

        foreach ($rows as $row) {
            $migrations[] = $row['migration'];
        }

        return $migrations;
    }
}
