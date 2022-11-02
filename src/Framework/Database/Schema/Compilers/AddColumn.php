<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class AddColumn
{
    public function compile(Table $table)
    {
        $columns = $table->columns();

        $columns->context('add');

        $sql = "ALTER TABLE {$table->getName()} ";
        $sql .= $columns->compile();

        if($constraints = $table->foreignKeys()->compile()) {
            $sql .= ', ' . $constraints;
        }

        $sql .= ";";

        return $sql;
    }
}
