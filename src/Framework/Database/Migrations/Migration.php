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
     * Get a new table instance.
     */
    private function table(string $table): Table
    {
        return new Table($table, $this->connection);
    }

    /**
     * Create a new table.
     */
    protected function create(string $table, callable $callback): void
    {
        $table = $this->table($table);

        $callback($table);

        $this->schema->createTable($table);
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

    protected function alter(string $table, callable $callback): void
    {
        $this->schema->alterTable($table)->modify($callback);
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
