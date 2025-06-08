<?php

namespace Lightpack\Database\Schema\Compilers;

class IndexKey
{
    public function compile(string|array $columns, string $indexType, ?string $indexName = null): string
    {
        // if the columns is a string, we are adding single index else composite index
        if(is_string($columns)) {
            return $this->compileSingleIndex($columns, $indexType, $indexName);
        }

        return $this->compileCompositeIndex($columns, $indexType, $indexName);
    }

    private function escapeColumn(string $column): string
    {
        // If already backticked, return as is
        if (str_starts_with($column, '`') && str_ends_with($column, '`')) {
            return $column;
        }
        return '`' . str_replace('`', '', $column) . '`';
    }

    private function compileSingleIndex(string $column, string $indexType, ?string $indexName = null): string
    {
        if(is_null($indexName)) {
            $indexName = $column . '_' . strtolower($indexType);
        }

        $column = $this->escapeColumn($column);

        $sql = "{$indexType} {$indexName} ({$column})";

        if($indexType === 'PRIMARY') {
            $sql = "{$indexType} KEY ({$column})";
        }

        return $sql;
    }

    /**
     * Compile a composite index definition.
     * If the auto-generated index name would exceed 60 chars, use a hash-based name (idx_{8charhash}).
     */
    private function compileCompositeIndex(array $columns, string $indexType, ?string $indexName = null): string
    {
        if (is_null($indexName)) {
            $base = implode('_', $columns) . '_' . strtolower($indexType);
            // MySQL and most DBs limit index names to 64 chars; use 60 as a safe threshold
            if (strlen($base) > 60) {
                $hash = substr(md5($base), 0, 8);
                $indexName = 'idx_' . $hash;
            } else {
                $indexName = $base;
            }
        }

        $columns = array_map([$this, 'escapeColumn'], $columns);
        $columns = implode(', ', $columns);

        $sql = "{$indexType} {$indexName} ({$columns})";

        if ($indexType === 'PRIMARY') {
            $sql = "{$indexType} KEY ({$columns})";
        }

        return $sql;
    }
}
