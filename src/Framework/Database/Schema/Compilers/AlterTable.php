<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ForeignKey;

class AlterTable
{
    public function compilePrimary(string $table, string|array $columns): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'PRIMARY');

        return $sql;
    }

    public function dropPrimary(string $table): string
    {
        $sql = "ALTER TABLE {$table} DROP PRIMARY KEY";

        return $sql;
    }

    public function compileUnique(string $table, string|array $columns, ?string $indexName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'UNIQUE', $indexName);

        return $sql;
    }

    public function compileDropUnique(string $table, string $indexName): string
    {
        $sql = "ALTER TABLE {$table} DROP INDEX {$indexName}";

        return $sql;
    }

    public function compileIndex(string $table, string|array $columns, ?string $indexName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'INDEX', $indexName);

        return $sql;
    }


    public function compileDropIndex(string $table, string $indexName): string
    {
        $sql = "ALTER TABLE {$table} DROP INDEX {$indexName}";

        return $sql;
    }

    public function compileFullText(string $table, string|array $columns, ?string $indexName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'FULLTEXT', $indexName);

        return $sql;
    }

    public function compileDropFullText(string $table, string $indexName): string
    {
        $sql = "ALTER TABLE {$table} DROP INDEX {$indexName}";

        return $sql;
    }

    public function compileSpatial(string $table, string|array $columns, ?string $indexName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'SPATIAL', $indexName);

        return $sql;
    }

    public function compileDropSpatial(string $table, string $indexName): string
    {
        $sql = "ALTER TABLE {$table} DROP INDEX {$indexName}";

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compilePrimaryKey(string $table, string|array $columns, ?string $indexName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new IndexKey)->compile($columns, 'PRIMARY', $indexName);

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compileDropPrimaryKey(string $table): string
    {
        $sql = "ALTER TABLE {$table} DROP PRIMARY KEY";

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compileForeignKey(string $table, string $column, string $referenceTable, string $referenceColumn, ?string $constraintName = null): string
    {
        $sql = "ALTER TABLE {$table} ADD ";
        $sql .= (new ForeignKey)->compile($column, $referenceTable, $referenceColumn, $constraintName);

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compileDropForeignKey(string $table, string $constraintName): string
    {
        $sql = "ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}";

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compileRename(string $table, string $newName): string
    {
        $sql = "ALTER TABLE {$table} RENAME TO {$newName}";

        return $sql;
    }

    /**
     * @todo: test if this works.
     */
    private function compileRenameColumn(string $table, string $column, string $newName): string
    {
        $sql = "ALTER TABLE {$table} CHANGE {$column} {$newName}";

        return $sql;
    }
}
