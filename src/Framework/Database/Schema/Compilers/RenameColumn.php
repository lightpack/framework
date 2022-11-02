<?php

namespace Lightpack\Database\Schema\Compilers;

class RenameColumn
{
    public function compile(string $table, string $oldColumn, string $newColumn): string
    {
        return "ALTER TABLE {$table} RENAME COLUMN {$oldColumn} TO {$newColumn};";
    }
}
