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
    protected function table(string $table): Table
    {
        return new Table($table, $this->connection);
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
