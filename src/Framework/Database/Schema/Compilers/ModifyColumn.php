<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class ModifyColumn
{
    public function compile(Table $table)
    {
        $columns = $table->columns();
        $columns->context('change');

        $sql = "ALTER TABLE {$table->getName()} ";
        $sql .= $columns->compile();

        if($constraints = $table->foreignKeys()->compile()) {
            $sql .= ', ' . $constraints;
        }

        $sql .= ";";

        return $sql;
    }
}
