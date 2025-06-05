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

    private function compileCompositeIndex(array $columns, string $indexType, ?string $indexName = null): string
    {
        if(is_null($indexName)) {
            $indexName = implode('_', $columns) . '_' . strtolower($indexType);
        }

        $columns = array_map([$this, 'escapeColumn'], $columns);
        $columns = implode(', ', $columns);

        $sql = "{$indexType} {$indexName} ({$columns})";

        if($indexType === 'PRIMARY') {
            $sql = "{$indexType} KEY ({$columns})";
        }

        return $sql;
    }
}
