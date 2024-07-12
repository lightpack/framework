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
     * It returns an array of table names.
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
     * 
     * It returns an array of index names.
     */
    public function inspectIndexes(string $table): array
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
     * It returns an array of the index details if found, otherwise null.
     */
    public function inspectIndex(string $table, string $index): ?array
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

        $result = $this->connection->query("
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $foreignKeys[] = $row;
        }

        return $foreignKeys;
    }

    /**
     * Inspect a foreign key in a table.
     * It returns an array of the foreign key details if found, otherwise null.
     */
    public function inspectForeignKey(string $table, string $foreignKey)
    {
        $result = $this->connection->query("
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$foreignKey'
        ");

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Inspect the list of fulltext indexes in a table.
     * 
     * It returns an array of fulltext index names.
     */
    public function inspectFullTextIndexes(string $table)
    {
        $fullTextIndexes = [];

        $result = $this->connection->query("
            SELECT TABLE_NAME, COLUMN_NAME, INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND INDEX_TYPE = 'FULLTEXT'
        ");

        foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $fullTextIndexes[] = $row;
        }

        return $fullTextIndexes;
    }

    /**
     * Inspect a fulltext index in a table.
     * It returns an array of the fulltext index details if found, otherwise null.
     */
    public function inspectFullTextIndex(string $table, string $index)
    {
        $result = $this->connection->query("
            SELECT TABLE_NAME, COLUMN_NAME, INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND INDEX_NAME = '$index' AND INDEX_TYPE = 'FULLTEXT'
        ");

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
