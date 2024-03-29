<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class CreateTable
{
    public function compile(Table $table): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table->getName()} (";
        $sql .= $table->columns()->compile();

        if ($indexes = $table->getIndexes()) {
            $sql .= ', ' . implode(', ', $indexes);
        }

        if ($constraints = $table->foreignKeys()->compile()) {
            $sql .= ', ' . $constraints;
        }


        $sql .= ") ENGINE={$table->getEngine()} DEFAULT CHARSET={$table->getCharset()} COLLATE={$table->getCollation()};";

        return $sql;
    }
}
