<?php

namespace Lightpack\Database\Schema\Compilers;

class IndexKey
{
    public function compile(string|array $columns, string $indexType, ?string $indexName = null): string
    {
        $columns = (array) $columns;

        if(is_null($indexName)) {
            $indexName = implode('_', $columns) . '_' . strtolower($indexType);
        }

        $columns = implode(', ', $columns);

        $sql = "{$indexType} KEY {$indexName} ({$columns})";

        if($indexType === 'PRIMARY') {
            $sql = "{$indexType} KEY ({$columns})";
        }

        return $sql;
    }
}
