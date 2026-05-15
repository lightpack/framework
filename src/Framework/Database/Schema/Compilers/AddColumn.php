<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class AddColumn
{
    public function compile(Table $table)
    {
        $columns = $table->columns();

        $columns->context('add');

        $parts = [];

        if ($columnSql = $columns->compile()) {
            $parts[] = $columnSql;
        }

        if ($constraints = $table->foreignKeys()->compile('alter')) {
            $parts[] = $constraints;
        }

        $sql = "ALTER TABLE {$table->getName()} " . implode(', ', $parts) . ";";

        return $sql;
    }
}
