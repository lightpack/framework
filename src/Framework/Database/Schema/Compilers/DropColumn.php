<?php

namespace Lightpack\Database\Schema\Compilers;

class DropColumn
{
    public function compile(string $table, string ...$columns): string
    {
        $sql = "ALTER TABLE {$table}";

        foreach($columns as $column) {
            $sql .= " DROP {$column},";
        }

        return rtrim($sql, ',') . ";";
    }
}
