<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class RenameColumn
{
    public function compile(Table $table)
    {
        $statements = [];

        foreach ($table->getRenameColumns() as $oldName => $newName) {
            $statements[] = "ALTER TABLE {$table->getName()} RENAME COLUMN {$oldName} TO {$newName};";
        }

        return implode(' ', $statements);
    }
}
