<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\DB;
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

    /**
     * Create a new table.
     *
     * @param Table $table
     * @return void
     */
    public function createTable(Table $table): string
    {
        $sql = (new CreateTable)->compile($table);

        return $sql;
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return void
     */
    public function dropTable(string $table): string
    {
        $sql = (new DropTable)->compile($table);

        return $sql;
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return void
     */
    public function truncateTable(string $table): string
    {
        $sql = (new TruncateTable)->compile($table);

        return $sql;
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
     */
    public function inspectColumns(string $table)
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
     */
    public function inspectColumn(string $table, string $column)
    {
        $rows = $this->connection->query('DESCRIBE ' . $table);

        while (($row = $rows->fetch())) {
            if ($column === $row['Field']) {
                return $row;
            }
        }

        return null;
    }
}
