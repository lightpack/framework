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

    private function compileSingleIndex(string $column, string $indexType, ?string $indexName = null): string
    {
        if(is_null($indexName)) {
            $indexName = $column . '_' . strtolower($indexType);
        }

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

        $columns = implode(', ', $columns);

        $sql = "{$indexType} {$indexName} ({$columns})";

        if($indexType === 'PRIMARY') {
            $sql = "{$indexType} KEY ({$columns})";
        }

        return $sql;
    }
}
