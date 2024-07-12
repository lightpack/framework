<?php

namespace Lightpack\Database\Migrations;

use Lightpack\Database\DB;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;

abstract class Migration
{
    /**
     * @var \Lightpack\Database\Schema\Schema
     */
    protected $schema;

    /**
     * @var \Lightpack\Database\DB
     */
    protected $connection;

    abstract public function up(): void;
    
    abstract public function down(): void;

    /**
     * Create a new table.
     */
    protected function create(string $table, callable $callback): void
    {
        $this->schema->createTable($table, $callback);
    }

    /**
     * Alter an existing table.
     */
    protected function alter(string $table): Table
    {
        return $this->schema->alterTable($table);
    }

    /**
     * Rename a table.
     */
    protected function rename(string $oldTable, string $newTable): void
    {
        $this->schema->renameTable($oldTable, $newTable);
    }

    /**
     * Drop a table.
     */
    protected function drop(string $table): void
    {
        $this->schema->dropTable($table);
    }

    /**
     * Truncate a table.
     */
    protected function truncate(string $table): void
    {
        $this->schema->truncateTable($table);
    }

    /**
     * Execute a raw SQL query.
     */
    protected function execute(string $sql): void
    {
        $this->connection->query($sql);
    }

    /**
     * Boot migration.
     * 
     * @internal This method is called by the migration runner.
     */
    public function boot(Schema $schema, DB $connection): void
    {
        $this->schema = $schema;
        $this->connection = $connection;
    }
}
