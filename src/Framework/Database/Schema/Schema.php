<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\DB;
use Lightpack\Database\Schema\Compilers\AlterTable;
use Lightpack\Database\Schema\Table;
use Lightpack\Database\Schema\Compilers\DropTable;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\TruncateTable;

class Schema
{
    public function __construct(private DB $connection)
    {
        // ...
    }

    public function createTable(string $table, callable $callback): void
    {
        $table = new Table($table, $this->connection);

        $callback($table);

        $sql = (new CreateTable)->compile($table);

        $this->connection->query($sql);
    }

    public function alterTable(string $table): Table
    {
        $table = new Table($table, $this->connection);
        
        return $table->alterContext();
    }

    /**
     * Drop a table.
     */
    public function dropTable(string $table): void
    {
        $sql = (new DropTable)->compile($table);

        $this->connection->query($sql);
    }

    /**
     * Truncate a table.
     */
    public function truncateTable(string $table): void
    {
        $sql = (new TruncateTable)->compile($table);

        $this->connection->query($sql);
    }

    public function renameTable(string $oldTable, string $newTable): void
    {
        $sql = (new AlterTable)->compileRename($oldTable, $newTable);

        $this->connection->query($sql);
    }
    
    /**
     * Inspect the list of tables in the database.
     */
    public function inspectTables(): array
    {
        $tables = [];

        $rows = $this->connection->query('SHOW TABLES');

        while (($row = $rows->fetch())) {
            foreach ($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

    /**
     * Inspect the list of columns in a table.
     * 
     * This method returns an array of column names.
     */
    public function inspectColumns(string $table): array
    {
        $columns = [];

        $rows = $this->connection->query('DESCRIBE ' . $table);

        while (($row = $rows->fetch())) {
            $columns[] = $row['Field'];
        }

        return $columns;
    }

    /**
     * Inspect a column in a table.
     * It returns an array of the column details if found, otherwise null.
     */
    public function inspectColumn(string $table, string $column): ?array
    {
        $rows = $this->connection->query('DESCRIBE ' . $table);

        while (($row = $rows->fetch())) {
            if ($column === $row['Field']) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Inspect the list of indexes in a table.
     */
    public function inspectIndexes(string $table)
    {
        $indexes = [];

        $rows = $this->connection->query('SHOW INDEXES FROM ' . $table);

        while (($row = $rows->fetch())) {
            $indexes[] = $row['Key_name'];
        }

        return $indexes;
    }

    /**
     * Inspect an index in a table.
     */
    public function inspectIndex(string $table, string $index)
    {
        $rows = $this->connection->query('SHOW INDEXES FROM ' . $table);

        while (($row = $rows->fetch())) {
            if ($index === $row['Key_name']) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Inspect the list of foreign keys in a table.
     */
    public function inspectForeignKeys(string $table)
    {
        $foreignKeys = [];

        $rows = $this->connection->query('SHOW CREATE TABLE ' . $table);

        while (($row = $rows->fetch())) {
            $foreignKeys[] = $row['Create Table'];
        }

        return $foreignKeys;
    }

    /**
     * Inspect a foreign key in a table.
     */
    public function inspectForeignKey(string $table, string $foreignKey)
    {
        $rows = $this->connection->query('SHOW CREATE TABLE ' . $table);

        while (($row = $rows->fetch())) {
            if (strpos($row['Create Table'], $foreignKey) !== false) {
                return $row;
            }
        }

        return null;
    }
}
